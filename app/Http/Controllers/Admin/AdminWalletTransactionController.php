<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminWalletTransactionController extends Controller
{
    public function index(Request $r)
    {
        [$txs, $stats] = $this->buildQueryAndStats($r);

        return view('admins.wallettx.index', [
            'txs' => $txs,
            'stats' => $stats,
        ]);
    }

    public function search(Request $r)
    {
        [$txs, $stats] = $this->buildQueryAndStats($r);

        $html = view('admins.wallettx.partials.table', ['txs' => $txs])->render();
        $pagination = $txs->links('vendor.pagination.admin')->render();

        return response()->json([
            'html' => $html,
            'pagination' => $pagination,
            'total' => $txs->total(),
            'stats' => $stats,
        ]);
    }

    public function modal(Request $r, WalletTransaction $tx)
    {
        $row = WalletTransaction::query()
            ->from('wallet_transactions as wt')
            ->leftJoin('users as u', 'u.id', '=', 'wt.user_id')
            ->select([
                'wt.*',
                'u.username as username',
                'u.email as email',
                'u.country as user_country',
                'u.currency as user_currency',
            ])
            ->where('wt.id', $tx->id)
            ->firstOrFail();

        $meta = $row->meta ?? null;

        $html = view('admins.wallettx.partials.modal', [
            'tx' => $row,
            'meta' => $meta,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function exportCsv(Request $r): StreamedResponse
    {
        [$q] = $this->buildQuery($r);

        $filename = 'wallet_transactions_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'id','occurred_at','created_at',
                'user_id','username','email',
                'wallet_id','wallet_type',
                'direction','status',
                'amount','balance_before','balance_after',
                'title','description',
                'provider','game_code','round_ref','bet_id',
                'reference','external_id','tx_hash',
                'created_by','approved_by','ip','user_agent'
            ]);

            $q->reorder()
                ->orderByDesc('wt.id')
                ->chunk(1000, function ($rows) use ($out) {
                    foreach ($rows as $x) {
                        fputcsv($out, [
                            $x->id ?? null,
                            $x->occurred_at ?? null,
                            $x->created_at ?? null,
                            $x->user_id ?? null,
                            $x->username ?? null,
                            $x->email ?? null,
                            $x->wallet_id ?? null,
                            $x->wallet_type ?? null,
                            $x->direction ?? null,
                            $x->status ?? null,
                            $x->amount ?? null,
                            $x->balance_before ?? null,
                            $x->balance_after ?? null,
                            $x->title ?? null,
                            $x->description ?? null,
                            $x->provider ?? null,
                            $x->game_code ?? null,
                            $x->round_ref ?? null,
                            $x->bet_id ?? null,
                            $x->reference ?? null,
                            $x->external_id ?? null,
                            $x->tx_hash ?? null,
                            $x->created_by ?? null,
                            $x->approved_by ?? null,
                            $x->ip ?? null,
                            $x->user_agent ?? null,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Safe update (does NOT touch balances).
     */
    public function updateTx(Request $r, WalletTransaction $tx)
    {
        $data = $r->validate([
            'status' => 'nullable|integer|in:0,1,2,3,4',
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:2000',
            'reference' => 'nullable|string|max:100',
            'external_id' => 'nullable|string|max:100',
            'tx_hash' => 'nullable|string|max:100',
            'provider' => 'nullable|string|max:10',
            'round_ref' => 'nullable|string|max:191',
            'bet_id' => 'nullable|string|max:191',
            'game_code' => 'nullable|string|max:191',
        ]);

        if (array_key_exists('reference', $data) && !empty($data['reference'])) {
            $exists = WalletTransaction::query()
                ->where('user_id', $tx->user_id)
                ->where('reference', $data['reference'])
                ->where('id', '!=', $tx->id)
                ->exists();

            if ($exists) {
                return response()->json(['ok' => false, 'message' => 'Reference already used for this user.'], 422);
            }
        }

        $tx->fill($data);

        $tx->meta = array_merge((array)($tx->meta ?? []), $this->actorMeta($r));
        $tx->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Admin adjusts wallet: creates NEW tx + updates wallet balance.
     * wallets table uses `type` column.
     */
    public function adminAdjust(Request $r)
    {
        $data = $r->validate([
            'user_id' => 'required|integer|exists:users,id',
            'wallet_type' => 'required|string|max:20',
            'direction' => 'required|string|in:credit,debit',
            'amount' => 'required|numeric|min:0.000000000000000001',
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($data, $r) {
            $wallet = Wallet::query()
                ->where('user_id', (int)$data['user_id'])
                ->where('type', $data['wallet_type'])
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                return response()->json(['ok' => false, 'message' => 'Wallet not found for this user/type.'], 422);
            }

            $before = (string)$wallet->balance;
            $amount = (string)$data['amount'];

            $after = $data['direction'] === WalletTransaction::DIR_CREDIT
                ? $this->decAdd($before, $amount)
                : $this->decSub($before, $amount);

            if ($this->decCmp0($after) < 0) {
                return response()->json(['ok' => false, 'message' => 'Resulting balance would be negative.'], 422);
            }

            $refBase = 'admin_adjust:' . now()->format('YmdHis') . ':' . (int)$data['user_id'];
            $ref = $refBase;
            $i = 0;

            while (WalletTransaction::query()
                ->where('user_id', (int)$data['user_id'])
                ->where('reference', $ref)
                ->exists()
            ) {
                $i++;
                $ref = $refBase . ':' . $i . ':' . Str::lower(Str::random(4));
            }

            $meta = array_merge(['admin_adjust' => true], $this->actorMeta($r));

            $tx = WalletTransaction::create([
                'user_id' => (int)$data['user_id'],
                'wallet_id' => $wallet->id,
                'wallet_type' => $wallet->type,

                'direction' => $data['direction'],
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,

                'status' => WalletTransaction::STATUS_COMPLETED,
                'reference' => $ref,

                'title' => $data['title'] ?: 'Admin Adjust',
                'description' => $data['description'] ?: null,

                // FK is users table; admin guard typically not a user record
                'created_by' => null,
                'approved_by' => null,

                'ip' => $r->ip(),
                'user_agent' => substr((string)$r->userAgent(), 0, 255),
                'meta' => $meta,
                'occurred_at' => now(),
            ]);

            $wallet->balance = $after;
            $wallet->save();

            return response()->json(['ok' => true, 'tx_id' => $tx->id]);
        });
    }

    /**
     * Reverse an existing tx: mark original REVERSED and create reversing tx + adjust wallet.
     */
    public function reverse(Request $r, WalletTransaction $tx)
    {
        $data = $r->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ((int)$tx->status !== WalletTransaction::STATUS_COMPLETED) {
            return response()->json(['ok' => false, 'message' => 'Only COMPLETED transactions can be reversed.'], 422);
        }

        $revRef = 'reverse_of:' . $tx->id;

        $exists = WalletTransaction::query()
            ->where('user_id', $tx->user_id)
            ->where('reference', $revRef)
            ->exists();

        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'This transaction already has a reversal.'], 422);
        }

        return DB::transaction(function () use ($tx, $r, $data, $revRef) {
            $wallet = Wallet::query()
                ->where('id', $tx->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (string)$wallet->balance;
            $amount = (string)$tx->amount;

            $revDir = $tx->direction === WalletTransaction::DIR_CREDIT
                ? WalletTransaction::DIR_DEBIT
                : WalletTransaction::DIR_CREDIT;

            $after = $revDir === WalletTransaction::DIR_CREDIT
                ? $this->decAdd($before, $amount)
                : $this->decSub($before, $amount);

            if ($this->decCmp0($after) < 0) {
                return response()->json(['ok' => false, 'message' => 'Reversal would make wallet negative.'], 422);
            }

            // mark original reversed
            $tx->status = WalletTransaction::STATUS_REVERSED;
            $tx->meta = array_merge((array)($tx->meta ?? []), [
                'reversed_at' => now()->toDateTimeString(),
            ], $this->actorMeta($r));
            $tx->save();

            $meta = array_merge(['reversal_of' => $tx->id], $this->actorMeta($r));

            $rev = WalletTransaction::create([
                'user_id' => $tx->user_id,
                'wallet_id' => $wallet->id,
                'wallet_type' => $tx->wallet_type,

                'direction' => $revDir,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,

                'status' => WalletTransaction::STATUS_COMPLETED,
                'reference' => $revRef,

                'provider' => $tx->provider,
                'round_ref' => $tx->round_ref,
                'bet_id' => $tx->bet_id,
                'game_code' => $tx->game_code,

                'title' => 'Reversal',
                'description' => trim('Reversal of #' . $tx->id . ' ' . ($data['reason'] ?? '')),

                'created_by' => null,
                'approved_by' => null,

                'ip' => $r->ip(),
                'user_agent' => substr((string)$r->userAgent(), 0, 255),
                'meta' => $meta,
                'occurred_at' => now(),
            ]);

            $wallet->balance = $after;
            $wallet->save();

            return response()->json(['ok' => true, 'reversal_id' => $rev->id]);
        });
    }

    // -------------------------
    // Query + Stats
    // -------------------------

    private function buildQueryAndStats(Request $r)
    {
        [$base] = $this->buildQuery($r);

        // ✅ stats query must not include wt.* with SUM()
        $statsQ = clone $base;

        $stats = $statsQ
            ->reorder()
            ->select(DB::raw("
                COALESCE(SUM(CASE WHEN wt.direction='credit' THEN wt.amount ELSE 0 END),0) as credit_sum,
                COALESCE(SUM(CASE WHEN wt.direction='debit' THEN wt.amount ELSE 0 END),0) as debit_sum
            "))
            ->first();

        $credit = (string)($stats->credit_sum ?? '0');
        $debit  = (string)($stats->debit_sum ?? '0');

        $statsOut = [
            'credit_sum' => $this->fmtMoney($credit),
            'debit_sum'  => $this->fmtMoney($debit),
            'net_sum'    => $this->fmtMoney($this->decSub($credit, $debit)),
        ];

        $txs = $base->orderByDesc('wt.id')->paginate(25)->withQueryString();

        return [$txs, $statsOut];
    }

    private function buildQuery(Request $r)
    {
        $q = trim((string)$r->get('q', ''));
        $userId = trim((string)$r->get('user_id', ''));
        $walletType = trim((string)$r->get('wallet_type', 'all'));
        $direction = trim((string)$r->get('direction', 'all'));
        $status = trim((string)$r->get('status', 'all'));

        $provider = trim((string)$r->get('provider', ''));
        $game = trim((string)$r->get('game', ''));
        $reference = trim((string)$r->get('reference', ''));
        $externalId = trim((string)$r->get('external_id', ''));
        $txHash = trim((string)$r->get('tx_hash', ''));

        $roundRef = trim((string)$r->get('round_ref', ''));
        $betId = trim((string)$r->get('bet_id', ''));

        $from = trim((string)$r->get('from', ''));
        $to = trim((string)$r->get('to', ''));
        $occurredFrom = trim((string)$r->get('occurred_from', ''));
        $occurredTo = trim((string)$r->get('occurred_to', ''));

        $minAmount = trim((string)$r->get('min_amount', ''));
        $maxAmount = trim((string)$r->get('max_amount', ''));

        $onlyAdmin = (string)$r->get('only_admin', '0') === '1';
        $onlyWithMeta = (string)$r->get('only_with_meta', '0') === '1';
        $onlyProvider = (string)$r->get('only_provider', '0') === '1';

        $query = WalletTransaction::query()
            ->from('wallet_transactions as wt')
            ->leftJoin('users as u', 'u.id', '=', 'wt.user_id')
            ->select([
                'wt.*',
                'u.username as username',
                'u.email as email',
            ]);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('u.username', 'like', "%{$q}%")
                    ->orWhere('u.email', 'like', "%{$q}%")
                    ->orWhere('wt.reference', 'like', "%{$q}%")
                    ->orWhere('wt.external_id', 'like', "%{$q}%")
                    ->orWhere('wt.tx_hash', 'like', "%{$q}%")
                    ->orWhere('wt.round_ref', 'like', "%{$q}%")
                    ->orWhere('wt.bet_id', 'like', "%{$q}%")
                    ->orWhere('wt.game_code', 'like', "%{$q}%")
                    ->orWhere('wt.title', 'like', "%{$q}%");
            });
        }

        if ($userId !== '') $query->where('wt.user_id', (int)$userId);

        if ($walletType !== 'all' && $walletType !== '') $query->where('wt.wallet_type', $walletType);
        if ($direction !== 'all' && $direction !== '') $query->where('wt.direction', $direction);

        if ($status !== 'all' && $status !== '') $query->where('wt.status', (int)$status);

        if ($provider !== '') $query->where('wt.provider', 'like', "%{$provider}%");
        if ($game !== '') $query->where('wt.game_code', 'like', "%{$game}%");

        if ($reference !== '') $query->where('wt.reference', 'like', "%{$reference}%");
        if ($externalId !== '') $query->where('wt.external_id', 'like', "%{$externalId}%");
        if ($txHash !== '') $query->where('wt.tx_hash', 'like', "%{$txHash}%");

        if ($roundRef !== '') $query->where('wt.round_ref', 'like', "%{$roundRef}%");
        if ($betId !== '') $query->where('wt.bet_id', 'like', "%{$betId}%");

        if ($from !== '') $query->whereDate('wt.created_at', '>=', $from);
        if ($to !== '') $query->whereDate('wt.created_at', '<=', $to);

        if ($occurredFrom !== '') $query->whereDate('wt.occurred_at', '>=', $occurredFrom);
        if ($occurredTo !== '') $query->whereDate('wt.occurred_at', '<=', $occurredTo);

        if ($minAmount !== '') $query->where('wt.amount', '>=', $minAmount);
        if ($maxAmount !== '') $query->where('wt.amount', '<=', $maxAmount);

        if ($onlyAdmin) $query->whereNotNull('wt.created_by');
        if ($onlyWithMeta) $query->whereNotNull('wt.meta');
        if ($onlyProvider) $query->whereNotNull('wt.provider');

        return [$query];
    }

    private function fmtMoney($v): string
    {
        $s = is_null($v) ? '0' : (string)$v;
        return number_format((float)$s, 2, '.', '');
    }

    // -------------------------
    // Decimal helpers (no bcmath)
    // -------------------------

    private function decToFloat(string $v): float
    {
        $v = trim($v);
        if ($v === '') return 0.0;
        return (float)$v;
    }

    private function decFmt18(float $v): string
    {
        return number_format($v, 18, '.', '');
    }

    private function decAdd(string $a, string $b): string
    {
        return $this->decFmt18($this->decToFloat($a) + $this->decToFloat($b));
    }

    private function decSub(string $a, string $b): string
    {
        return $this->decFmt18($this->decToFloat($a) - $this->decToFloat($b));
    }

    private function decCmp0(string $a): int
    {
        $fa = $this->decToFloat($a);
        if ($fa > 0) return 1;
        if ($fa < 0) return -1;
        return 0;
    }

    // -------------------------
    // Admin identity in meta
    // -------------------------

    private function actorMeta(Request $r): array
    {
        $admin = Auth::guard('admin')->user();

        return array_filter([
            'actor_admin' => $admin ? ($admin->username ?? $admin->id ?? 'admin') : null,
            'actor_ip' => $r->ip(),
            'actor_ua' => substr((string)$r->userAgent(), 0, 255),
            'actor_at' => now()->toDateTimeString(),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
