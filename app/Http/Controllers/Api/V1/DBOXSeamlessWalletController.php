<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BetRecord;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DBOXSeamlessWalletController extends Controller
{
    private const GAME_WALLET_TYPE = Wallet::TYPE_CHIPS;
    private const SCALE = 2;

    // log channels (config/logging.php)
    private const CH_API = 'api_daily';
    private const CH_BET = 'bethistory_daily';
    private const CH_SYS = 'system_daily';

    /**
     * Providers that use totPytAmt as payout amount (doc: 4.3.6 / 4.7.6).
     * If used, ignore pytAmt in txns.
     */
    private const TOT_PYT_PROVIDERS = ['BTI'];

    private function traceId(): string
    {
        try {
            return bin2hex(random_bytes(8)); // 16 chars
        } catch (\Throwable $e) {
            return (string) uniqid('t', true);
        }
    }

    private function sep(string $channel, array $ctx = []): void
    {
        Log::channel($channel)->info(str_repeat('=', 70), $ctx);
    }

    private function info(string $channel, string $msg, array $ctx = []): void
    {
        Log::channel($channel)->info($msg, $ctx);
    }

    private function error(string $channel, string $msg, array $ctx = []): void
    {
        Log::channel($channel)->error($msg, $ctx);
    }

    private function findUserByMerPlyId(string $merPlyId): ?User
    {
        if (preg_match('/^LMH_(\d+)_/i', $merPlyId, $m)) {
            return User::find((int) $m[1]);
        }

        if (ctype_digit($merPlyId)) {
            return User::find((int) $merPlyId);
        }

        return null;
    }

    private function getGameWalletForUser(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id, 'type' => self::GAME_WALLET_TYPE],
            ['balance' => '0', 'status' => Wallet::STATUS_ACTIVE]
        );
    }

    private function roundRefFromUnqTxnId(string $unqTxnId): string
    {
        if (preg_match('/^[PS]_[PS]_(.+)$/', $unqTxnId, $m)) {
            return (string) $m[1];
        }
        return $unqTxnId;
    }

    private function normalizeAmount($val): string
    {
        if ($val === null) return $this->bc('0');

        $s = is_string($val) ? trim($val) : (string) $val;
        $s = str_replace(',', '', $s);
        if ($s === '' || $s === '.') $s = '0';
        if (!preg_match('/^-?\d+(\.\d+)?$/', $s)) $s = '0';

        return $this->bc($s);
    }

    private function bc(string $n): string
    {
        if (function_exists('bcadd')) return bcadd($n, '0', self::SCALE);
        return number_format((float) $n, self::SCALE, '.', '');
    }

    private function add(string $a, string $b): string
    {
        return function_exists('bcadd')
            ? bcadd($a, $b, self::SCALE)
            : number_format(((float) $a + (float) $b), self::SCALE, '.', '');
    }

    private function sub(string $a, string $b): string
    {
        return function_exists('bcsub')
            ? bcsub($a, $b, self::SCALE)
            : number_format(((float) $a - (float) $b), self::SCALE, '.', '');
    }

    private function cmp(string $a, string $b): int
    {
        return function_exists('bccomp')
            ? bccomp($a, $b, self::SCALE)
            : (((float) $a < (float) $b) ? -1 : (((float) $a > (float) $b) ? 1 : 0));
    }

    private function abs(string $a): string
    {
        return str_starts_with($a, '-') ? substr($a, 1) : $a;
    }

    private function isNegative(string $a): bool
    {
        return str_starts_with($a, '-');
    }

    private function sumTxns(array $txns, string $key): string
    {
        $sum = $this->bc('0');
        foreach ($txns as $t) {
            if (array_key_exists($key, $t) && $t[$key] !== null) {
                $sum = $this->add($sum, $this->normalizeAmount($t[$key]));
            }
        }
        return $sum;
    }

    // response balance (numeric) + log balance (string)
    private function blcStr(string $blc): string
    {
        return $this->bc($blc); // "794.60"
    }

    private function blcNum(string $blc): float
    {
        // keep response numeric, but logs should use blcStr() to avoid float noise
        return (float) $this->bc($blc);
    }

    private function parseProviderDate(?string $s): ?\Carbon\Carbon
    {
        if (!$s) return null;
        try {
            return \Carbon\Carbon::parse($s);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function providerUsesTotPytAmt(string $prvCode): bool
    {
        return in_array(strtoupper($prvCode), self::TOT_PYT_PROVIDERS, true);
    }

    /**
     * Compute payout amount for settle.
     *
     * Returns:
     *  - payout: string (>= 0)
     *  - source: string
     *  - winLoss: string|null (if derived)
     */
    private function computePayout(array $v, string $provider, string $stake): array
    {
        $txns = $v['txns'] ?? [];
        $totPytAmt = $v['totPytAmt'] ?? null;
    
        // 1) Provider uses totPytAmt => override everything else (doc)
        if ($this->providerUsesTotPytAmt($provider) && $totPytAmt !== null) {
            $p = $this->normalizeAmount($totPytAmt);
            if ($this->isNegative($p)) $p = $this->bc('0');
            return ['payout' => $p, 'source' => 'totPytAmt', 'winLoss' => null];
        }
    
        // 2) Prefer pytAmt (most common per doc)
        $hasPyt = false;
        foreach ($txns as $t) {
            if (array_key_exists('pytAmt', $t) && $t['pytAmt'] !== null) {
                $hasPyt = true;
                break;
            }
        }
    
        if ($hasPyt) {
            $sumPyt = $this->sumTxns($txns, 'pytAmt');
    
            // Heuristic: some providers send win/loss as negative pytAmt
            if ($this->isNegative($sumPyt) && $this->cmp($this->abs($sumPyt), $stake) <= 0) {
                $p = $this->add($stake, $sumPyt);
                if ($this->isNegative($p)) $p = $this->bc('0');
                return ['payout' => $p, 'source' => 'stake+pytAmt(winLossHeuristic)', 'winLoss' => $sumPyt];
            }
    
            $p = $sumPyt;
            if ($this->isNegative($p)) $p = $this->bc('0');
            return ['payout' => $p, 'source' => 'pytAmt', 'winLoss' => null];
        }
    
        // 3) Fallback: totPytAmt if provided (when not BTI or doc still sends it)
        if ($totPytAmt !== null) {
            $p = $this->normalizeAmount($totPytAmt);
            if ($this->isNegative($p)) $p = $this->bc('0');
            return ['payout' => $p, 'source' => 'totPytAmt_fallback', 'winLoss' => null];
        }
    
        // 4) Last resort: winLossAmt as reference (only if nothing else exists)
        $hasWinLoss = false;
        foreach ($txns as $t) {
            if (array_key_exists('winLossAmt', $t) && $t['winLossAmt'] !== null) {
                $hasWinLoss = true;
                break;
            }
        }
    
        if ($hasWinLoss) {
            $wl = $this->sumTxns($txns, 'winLossAmt');
            $p = $this->add($stake, $wl);
            if ($this->isNegative($p)) $p = $this->bc('0');
            return ['payout' => $p, 'source' => 'stake+winLossAmt', 'winLoss' => $wl];
        }
    
        return ['payout' => $this->bc('0'), 'source' => 'none', 'winLoss' => null];
    }


    private function isTransientDbError(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        // MySQL common transient errors
        if (str_contains($msg, 'Deadlock found')) return true;
        if (str_contains($msg, 'Lock wait timeout exceeded')) return true;

        // SQLSTATE 40001 (serialization failure), 1213 deadlock, 1205 lock timeout etc.
        if ($e instanceof QueryException) {
            $code = (string) ($e->errorInfo[1] ?? '');
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            if ($sqlState === '40001') return true;
            if ($code === '1213' || $code === '1205') return true;
        }

        return false;
    }

    public function getBalance(Request $request)
    {
        $trace = $this->traceId();
        $base = [
            'trace' => $trace,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ];

        $this->sep(self::CH_API, $base);

        $v = $request->validate([
            'merPlyId' => ['required', 'string', 'max:200'],
            'curCode'  => ['required', 'string', 'size:3'],
            'prvCode'  => ['required', 'string', 'size:3'],
        ]);

        $user = $this->findUserByMerPlyId($v['merPlyId']);

        if (!$user) {
            $this->error(self::CH_API, 'DBOX getBalance player_not_found', $base + [
                'merPlyId' => $v['merPlyId'],
                'curCode' => $v['curCode'],
                'prvCode' => $v['prvCode'],
            ]);

            return response()->json(['code' => -1, 'msg' => 'Player not found', 'data' => null]);
        }

        $wallet = $this->getGameWalletForUser($user);

        $this->info(self::CH_API, 'DBOX getBalance OK', $base + [
            'user_id' => $user->id,
            'merPlyId' => $v['merPlyId'],
            'curCode' => $v['curCode'],
            'prvCode' => $v['prvCode'],
            'wallet_id' => $wallet->id,
            'wallet_type' => $wallet->type,
            'blc_raw' => (string) $wallet->balance,
            'blc_str' => $this->blcStr((string) $wallet->balance),
        ]);

        return response()->json([
            'code' => 0,
            'msg'  => 'Success',
            'data' => ['blc' => $this->blcNum((string) $wallet->balance)],
        ]);
    }

    public function bet(Request $request)
    {
        $trace = $this->traceId();
        $base = [
            'trace' => $trace,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ];

        $this->sep(self::CH_API, $base);

        $v = $request->validate([
            'rsvId' => ['nullable'],
            'txns'  => ['required', 'array', 'min:1'],

            'txns.*.unqTxnId'    => ['required', 'string', 'max:200'],
            'txns.*.merPlyId'    => ['required', 'string', 'max:200'],
            'txns.*.curCode'     => ['required', 'string', 'size:3'],
            'txns.*.merCode'     => ['nullable', 'string', 'max:50'],
            'txns.*.betId'       => ['nullable', 'string', 'max:200'],
            'txns.*.totBetAmt'   => ['required', 'numeric'],
            'txns.*.betDt'       => ['nullable', 'string', 'max:50'],
            'txns.*.gmeCode'     => ['nullable', 'string', 'max:100'],
            'txns.*.prvCode'     => ['required', 'string', 'size:3'],
            'txns.*.betRsltCode' => ['nullable', 'string', 'max:50'],
        ]);

        $t0 = $v['txns'][0];
        $user = $this->findUserByMerPlyId((string) $t0['merPlyId']);

        $unqTxnId = (string) $t0['unqTxnId'];
        $roundRef = $this->roundRefFromUnqTxnId($unqTxnId);
        $provider = (string) ($t0['prvCode'] ?? '');
        $curCode  = (string) ($t0['curCode'] ?? '');
        $betAt    = $this->parseProviderDate($t0['betDt'] ?? null) ?? now();

        $stake = $this->sumTxns($v['txns'], 'totBetAmt');

        $ctx = $base + [
            'kind' => 'bet',
            'user_id' => $user?->id,
            'merPlyId' => (string) $t0['merPlyId'],
            'unqTxnId' => $unqTxnId,
            'round_ref' => $roundRef,
            'provider' => $provider,
            'curCode' => $curCode,
            'stake' => $stake,
            'txns_count' => count($v['txns']),
            'rsvId' => $v['rsvId'] ?? null,
        ];

        if (!$user) {
            $this->error(self::CH_API, 'DBOX bet player_not_found', $ctx);
            return response()->json(['code' => -1, 'msg' => 'Player not found', 'data' => null]);
        }

        $this->sep(self::CH_BET, $ctx);
        $this->info(self::CH_API, 'DBOX bet request', $ctx);

        // idempotent quick return
        $existing = WalletTransaction::where('user_id', $user->id)
            ->where('reference', $unqTxnId)
            ->first();

        if ($existing) {
            $this->info(self::CH_API, 'DBOX bet idempotent_hit', $ctx + [
                'wallet_tx_id' => $existing->id,
                'balance_after' => (string) $existing->balance_after,
                'balance_after_str' => $this->blcStr((string) $existing->balance_after),
            ]);

            $this->info(self::CH_BET, 'DBOX bet idempotent_hit', $ctx + [
                'wallet_tx_id' => $existing->id,
                'balance_after' => (string) $existing->balance_after,
                'balance_after_str' => $this->blcStr((string) $existing->balance_after),
            ]);

            return response()->json([
                'code' => 0,
                'msg'  => 'Success',
                'data' => ['blc' => $this->blcNum((string) $existing->balance_after)],
            ]);
        }

        // settle already done for this round? skip debit
        $alreadySettled = WalletTransaction::where('user_id', $user->id)
            ->where('title', 'DBOX Settle')
            ->where('round_ref', $roundRef)
            ->exists();

        if ($alreadySettled) {
            $wallet = $this->getGameWalletForUser($user);

            $this->info(self::CH_API, 'DBOX bet skipped_already_settled', $ctx + [
                'wallet_id' => $wallet->id,
                'wallet_type' => $wallet->type,
                'balance_str' => $this->blcStr((string) $wallet->balance),
            ]);

            $this->info(self::CH_BET, 'DBOX bet skipped_already_settled', $ctx + [
                'wallet_id' => $wallet->id,
                'wallet_type' => $wallet->type,
                'balance_str' => $this->blcStr((string) $wallet->balance),
            ]);

            return response()->json([
                'code' => 0,
                'msg'  => 'Success',
                'data' => ['blc' => $this->blcNum((string) $wallet->balance)],
            ]);
        }

        try {
            $result = DB::transaction(function () use ($user, $v, $t0, $stake, $unqTxnId, $roundRef, $provider, $curCode, $betAt) {
                $wallet = $this->getGameWalletForUser($user);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                if ((int) $wallet->status !== Wallet::STATUS_ACTIVE) {
                    throw new \RuntimeException('Wallet not active');
                }

                $before = (string) $wallet->balance;

                if ($this->cmp($before, $stake) < 0) {
                    return [
                        'insufficient' => true,
                        'before' => $before,
                        'wallet_id' => $wallet->id,
                        'wallet_type' => $wallet->type,
                    ];
                }

                $after = $this->sub($before, $stake);

                $wallet->balance = $after;
                $wallet->save();

                $tx = WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $wallet->id,
                    'wallet_type'    => $wallet->type,
                    'direction'      => WalletTransaction::DIR_DEBIT,
                    'amount'         => $stake,
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'status'         => WalletTransaction::STATUS_COMPLETED,
                    'reference'      => $unqTxnId,

                    'provider'       => $provider ?: null,
                    'round_ref'      => $roundRef,
                    'bet_id'         => $t0['betId'] ?? null,
                    'game_code'      => $t0['gmeCode'] ?? null,

                    'title'          => 'DBOX Bet',
                    'description'    => 'Seamless wallet stake debit',
                    'created_by'     => $user->id,
                    'meta'           => [
                        'kind'  => 'bet',
                        'rsvId' => $v['rsvId'] ?? null,
                        'txns'  => $v['txns'],
                    ],
                    'occurred_at'    => now(),
                ]);

                // bet_records (1 row per round)
                $br = BetRecord::where('user_id', $user->id)
                    ->where('provider', $provider ?: null)
                    ->where('round_ref', $roundRef)
                    ->lockForUpdate()
                    ->first();

                if (!$br) {
                    $br = new BetRecord();
                    $br->user_id = $user->id;
                    $br->provider = $provider ?: null;
                    $br->round_ref = $roundRef;
                }

                $br->bet_id = $t0['betId'] ?? $br->bet_id;
                $br->game_code = $t0['gmeCode'] ?? $br->game_code;
                $br->currency = $curCode ?: $br->currency;
                $br->wallet_type = $wallet->type;
                $br->stake_amount = $stake;
                $br->bet_reference = $unqTxnId;
                $br->bet_at = $br->bet_at ?? $betAt;
                $br->status = $br->status === 'settled' ? 'settled' : 'open';
                $br->meta = array_merge((array) ($br->meta ?? []), [
                    'bet_txns' => $v['txns'],
                ]);
                $br->save();

                return [
                    'insufficient' => false,
                    'before' => $before,
                    'after' => $after,
                    'wallet_id' => $wallet->id,
                    'wallet_type' => $wallet->type,
                    'wallet_tx_id' => $tx->id,
                    'bet_record_id' => $br->id,
                ];
            });

            if (!empty($result['insufficient'])) {
                $this->info(self::CH_API, 'DBOX bet insufficient_balance', $ctx + [
                    'wallet_id' => $result['wallet_id'] ?? null,
                    'wallet_type' => $result['wallet_type'] ?? null,
                    'before_str' => $this->blcStr((string) ($result['before'] ?? '0')),
                    'stake' => $stake,
                ]);

                $this->info(self::CH_BET, 'DBOX bet insufficient_balance', $ctx + [
                    'wallet_id' => $result['wallet_id'] ?? null,
                    'wallet_type' => $result['wallet_type'] ?? null,
                    'before_str' => $this->blcStr((string) ($result['before'] ?? '0')),
                    'stake' => $stake,
                ]);

                return response()->json([
                    'code' => 40041,
                    'msg'  => 'Insufficient Balance',
                    'data' => ['blc' => $this->blcNum((string) ($result['before'] ?? '0'))],
                ]);
            }

            $this->info(self::CH_API, 'DBOX bet success', $ctx + [
                'wallet_id' => $result['wallet_id'] ?? null,
                'wallet_type' => $result['wallet_type'] ?? null,
                'wallet_tx_id' => $result['wallet_tx_id'] ?? null,
                'bet_record_id' => $result['bet_record_id'] ?? null,
                'before_str' => $this->blcStr((string) ($result['before'] ?? '0')),
                'after_str' => $this->blcStr((string) ($result['after'] ?? '0')),
            ]);

            $this->info(self::CH_BET, 'DBOX bet success', $ctx + [
                'wallet_id' => $result['wallet_id'] ?? null,
                'wallet_type' => $result['wallet_type'] ?? null,
                'wallet_tx_id' => $result['wallet_tx_id'] ?? null,
                'bet_record_id' => $result['bet_record_id'] ?? null,
                'before_str' => $this->blcStr((string) ($result['before'] ?? '0')),
                'after_str' => $this->blcStr((string) ($result['after'] ?? '0')),
                'stake' => $stake,
            ]);

            return response()->json([
                'code' => 0,
                'msg'  => 'Success',
                'data' => ['blc' => $this->blcNum((string) ($result['after'] ?? '0'))],
            ]);
        } catch (\Throwable $e) {
            if ($this->isTransientDbError($e)) {
                if (strtoupper($provider) === 'MKK') {
                    $this->error(self::CH_API, 'DBOX transient_db_error -> 40053', $ctx + ['err' => $e->getMessage()]);
                    return response()->json(['code' => 40053, 'msg' => 'Retry Request', 'data' => null]);
                }
            
                $this->error(self::CH_API, 'DBOX transient_db_error (non-MKK) -> -1', $ctx + ['err' => $e->getMessage()]);
                return response()->json(['code' => -1, 'msg' => 'Failed', 'data' => null]);
            }


            $this->error(self::CH_API, 'DBOX bet failed', $ctx + ['err' => $e->getMessage()]);
            $this->error(self::CH_SYS, 'DBOX bet failed', $ctx + ['err' => $e->getMessage()]);

            return response()->json(['code' => -1, 'msg' => 'Failed', 'data' => null]);
        }
    }

    public function settle(Request $request)
    {
        $trace = $this->traceId();
        $base = [
            'trace' => $trace,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ];

        $this->sep(self::CH_API, $base);

        $v = $request->validate([
            'totPytAmt' => ['nullable', 'numeric'],
            'txns'      => ['required', 'array', 'min:1'],

            'txns.*.unqTxnId'   => ['required', 'string', 'max:200'],
            'txns.*.merPlyId'   => ['required', 'string', 'max:200'],
            'txns.*.curCode'    => ['required', 'string', 'size:3'],
            'txns.*.prvCode'    => ['required', 'string', 'size:3'],

            'txns.*.betId'      => ['nullable', 'string', 'max:200'],
            'txns.*.totBetAmt'  => ['nullable', 'numeric'],
            'txns.*.pytAmt'     => ['nullable', 'numeric'],
            'txns.*.gmeCode'    => ['nullable', 'string', 'max:100'],
            'txns.*.winLossAmt' => ['nullable', 'numeric'],
        ]);

        $t0 = $v['txns'][0];
        $user = $this->findUserByMerPlyId((string) $t0['merPlyId']);

        $unqTxnId = (string) $t0['unqTxnId'];
        $roundRef = $this->roundRefFromUnqTxnId($unqTxnId);
        $provider = (string) ($t0['prvCode'] ?? '');
        $curCode  = (string) ($t0['curCode'] ?? '');

        $stake = $this->sumTxns($v['txns'], 'totBetAmt');

        $payoutInfo = $this->computePayout($v, $provider, $stake);
        $payout = $payoutInfo['payout'];

        $ctx = $base + [
            'kind' => 'settle',
            'user_id' => $user?->id,
            'merPlyId' => (string) $t0['merPlyId'],
            'unqTxnId' => $unqTxnId,
            'round_ref' => $roundRef,
            'provider' => $provider,
            'curCode' => $curCode,
            'stake' => $stake,
            'payout' => $payout,
            'payout_source' => $payoutInfo['source'],
            'winLoss' => $payoutInfo['winLoss'],
            'txns_count' => count($v['txns']),
            'totPytAmt' => $v['totPytAmt'] ?? null,
        ];

        if (!$user) {
            $this->error(self::CH_API, 'DBOX settle player_not_found', $ctx);
            return response()->json(['code' => -1, 'msg' => 'Player not found', 'data' => null]);
        }

        $this->sep(self::CH_BET, $ctx);
        $this->info(self::CH_API, 'DBOX settle request', $ctx);

        // idempotent
        $existing = WalletTransaction::where('user_id', $user->id)
            ->where('reference', $unqTxnId)
            ->first();

        if ($existing) {
            $this->info(self::CH_API, 'DBOX settle idempotent_hit', $ctx + [
                'wallet_tx_id' => $existing->id,
                'balance_after_str' => $this->blcStr((string) $existing->balance_after),
            ]);

            $this->info(self::CH_BET, 'DBOX settle idempotent_hit', $ctx + [
                'wallet_tx_id' => $existing->id,
                'balance_after_str' => $this->blcStr((string) $existing->balance_after),
            ]);

            return response()->json([
                'code' => 0,
                'msg'  => 'Success',
                'data' => ['blc' => $this->blcNum((string) $existing->balance_after)],
            ]);
        }

        // bet exists?
        $hasBetTxn = WalletTransaction::where('user_id', $user->id)
            ->where('title', 'DBOX Bet')
            ->where('round_ref', $roundRef)
            ->exists();

        // delta:
        // bet already debited => +payout
        // bet missing => net apply (payout - stake) (place-and-settle behavior)
        $delta = $hasBetTxn ? $payout : $this->sub($payout, $stake);

        $this->info(self::CH_BET, 'DBOX settle computed', $ctx + [
            'hasBetTxn' => $hasBetTxn,
            'delta' => $delta,
        ]);

        try {
            $out = DB::transaction(function () use ($user, $v, $t0, $unqTxnId, $roundRef, $provider, $curCode, $stake, $payout, $delta, $hasBetTxn) {
                $wallet = $this->getGameWalletForUser($user);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                if ((int) $wallet->status !== Wallet::STATUS_ACTIVE) {
                    throw new \RuntimeException('Wallet not active');
                }

                $before = (string) $wallet->balance;

                // Place-and-settle can be negative (player lost) => must check insufficient per doc (40041)
                if (!$hasBetTxn && $this->isNegative($delta)) {
                    $need = $this->abs($delta);
                    if ($this->cmp($before, $need) < 0) {
                        return [
                            'insufficient' => true,
                            'before' => $before,
                            'wallet_id' => $wallet->id,
                            'wallet_type' => $wallet->type,
                        ];
                    }
                }

                $after = $this->add($before, $delta);
                if ($this->isNegative($after)) $after = $this->bc('0');

                $wallet->balance = $after;
                $wallet->save();

                $dir = $this->isNegative($delta) ? WalletTransaction::DIR_DEBIT : WalletTransaction::DIR_CREDIT;
                $amt = $this->abs($delta);

                $tx = WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $wallet->id,
                    'wallet_type'    => $wallet->type,
                    'direction'      => $dir,
                    'amount'         => $amt,
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'status'         => WalletTransaction::STATUS_COMPLETED,
                    'reference'      => $unqTxnId,

                    'provider'       => $provider ?: null,
                    'round_ref'      => $roundRef,
                    'bet_id'         => $t0['betId'] ?? null,
                    'game_code'      => $t0['gmeCode'] ?? null,

                    'title'          => 'DBOX Settle',
                    'description'    => $hasBetTxn ? 'Seamless settle payout credit' : 'Seamless place-and-settle net apply',
                    'created_by'     => $user->id,
                    'meta'           => [
                        'kind'      => 'settle',
                        'hasBetTxn' => $hasBetTxn,
                        'stake'     => $stake,
                        'payout'    => $payout,
                        'delta'     => $delta,
                        'totPytAmt' => $v['totPytAmt'] ?? null,
                        'txns'      => $v['txns'],
                    ],
                    'occurred_at'    => now(),
                ]);

                // bet_records upsert
                $br = BetRecord::where('user_id', $user->id)
                    ->where('provider', $provider ?: null)
                    ->where('round_ref', $roundRef)
                    ->lockForUpdate()
                    ->first();

                if (!$br) {
                    $br = new BetRecord();
                    $br->user_id = $user->id;
                    $br->provider = $provider ?: null;
                    $br->round_ref = $roundRef;
                    $br->bet_at = now();
                }

                $br->bet_id = $t0['betId'] ?? $br->bet_id;
                $br->game_code = $t0['gmeCode'] ?? $br->game_code;
                $br->currency = $curCode ?: $br->currency;
                $br->wallet_type = $wallet->type;

                $br->stake_amount = $stake;
                $br->payout_amount = $payout;
                $br->profit_amount = $this->sub($payout, $stake);

                $br->settle_reference = $unqTxnId;
                $br->settled_at = now();
                $br->status = 'settled';
                $br->meta = array_merge((array) ($br->meta ?? []), [
                    'settle_txns' => $v['txns'],
                    'hasBetTxn'   => $hasBetTxn,
                    'delta'       => $delta,
                ]);
                $br->save();

                return [
                    'insufficient' => false,
                    'wallet_id' => $wallet->id,
                    'wallet_type' => $wallet->type,
                    'wallet_tx_id' => $tx->id,
                    'bet_record_id' => $br->id,
                    'before' => $before,
                    'after' => $after,
                ];
            });

            if (!empty($out['insufficient'])) {
                return response()->json([
                    'code' => 40041,
                    'msg'  => 'Insufficient Balance',
                    'data' => ['blc' => $this->blcNum((string) ($out['before'] ?? '0'))],
                ]);
            }

            $this->info(self::CH_API, 'DBOX settle success', $ctx + [
                'hasBetTxn' => $hasBetTxn,
                'delta' => $delta,
                'wallet_id' => $out['wallet_id'] ?? null,
                'wallet_type' => $out['wallet_type'] ?? null,
                'wallet_tx_id' => $out['wallet_tx_id'] ?? null,
                'bet_record_id' => $out['bet_record_id'] ?? null,
                'before_str' => $this->blcStr((string) ($out['before'] ?? '0')),
                'after_str' => $this->blcStr((string) ($out['after'] ?? '0')),
            ]);

            $this->info(self::CH_BET, 'DBOX settle success', $ctx + [
                'hasBetTxn' => $hasBetTxn,
                'delta' => $delta,
                'wallet_id' => $out['wallet_id'] ?? null,
                'wallet_type' => $out['wallet_type'] ?? null,
                'wallet_tx_id' => $out['wallet_tx_id'] ?? null,
                'bet_record_id' => $out['bet_record_id'] ?? null,
                'before_str' => $this->blcStr((string) ($out['before'] ?? '0')),
                'after_str' => $this->blcStr((string) ($out['after'] ?? '0')),
            ]);

            return response()->json([
                'code' => 0,
                'msg'  => 'Success',
                'data' => ['blc' => $this->blcNum((string) ($out['after'] ?? '0'))],
            ]);
        } catch (\Throwable $e) {
            if ($this->isTransientDbError($e)) {
                if (strtoupper($provider) === 'MKK') {
                    $this->error(self::CH_API, 'DBOX transient_db_error -> 40053', $ctx + ['err' => $e->getMessage()]);
                    return response()->json(['code' => 40053, 'msg' => 'Retry Request', 'data' => null]);
                }
            
                $this->error(self::CH_API, 'DBOX transient_db_error (non-MKK) -> -1', $ctx + ['err' => $e->getMessage()]);
                return response()->json(['code' => -1, 'msg' => 'Failed', 'data' => null]);
            }


            $this->error(self::CH_API, 'DBOX settle failed', $ctx + ['err' => $e->getMessage()]);
            $this->error(self::CH_SYS, 'DBOX settle failed', $ctx + ['err' => $e->getMessage()]);
            return response()->json(['code' => -1, 'msg' => 'Failed', 'data' => null]);
        }
    }
}
