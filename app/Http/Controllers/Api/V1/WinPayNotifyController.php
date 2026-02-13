<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\Promotion;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Payments\WinPayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WinPayNotifyController extends Controller
{
    public function handle(Request $request)
    {
        $cid = 'winpay_notify_' . now()->format('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12);

        // 1) IP whitelist (doc says callback IP: 43.128.238.109)
        $allowedIp = (string) config('services.winpay.callback_ip', '43.128.238.109');
        $ip = $request->ip();

        if ($allowedIp !== '' && $ip !== $allowedIp) {
            Log::channel('winpay_daily')->warning('WINPAY notify blocked by IP', [
                'cid' => $cid,
                'ip' => $ip,
                'allowed' => $allowedIp,
            ]);
            // Usually still reply OK to avoid re-tries from spoofed senders
            return response('OK', 200);
        }

        $raw  = (string) $request->getContent();
        $data = $request->all();

        Log::channel('winpay_daily')->info('WINPAY notify: received', [
            'cid' => $cid,
            'ip' => $ip,
            'ua' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'content_type' => $request->header('Content-Type'),
            'raw_first_8000' => mb_substr($raw, 0, 8000),
            'json' => $this->maskArray(is_array($data) ? $data : []),
        ]);

        try {
            // 2) Verify signature
            $client = WinPayClient::make();
            $ok = $client->verifySign(is_array($data) ? $data : [], $cid, 'notify');

            if (!$ok) {
                Log::channel('winpay_daily')->warning('WINPAY notify: bad sign', [
                    'cid' => $cid,
                    'ip' => $ip,
                ]);
                return response('BAD_SIGN', 400);
            }

            // 3) Parse fields
            $billNumber = (string) ($data['bill_number'] ?? '');
            $status     = (string) ($data['status'] ?? '');
            $amountStr  = (string) ($data['amount'] ?? '0');
            $timestamp  = (string) ($data['timestamp'] ?? '');
            $remark     = (string) ($data['remark'] ?? '');

            if ($billNumber === '') {
                Log::channel('winpay_daily')->warning('WINPAY notify: missing bill_number', [
                    'cid' => $cid,
                    'data' => $this->maskArray(is_array($data) ? $data : []),
                ]);
                return response('OK', 200);
            }

            // 4) Idempotent crediting (transaction + row locks)
            DB::transaction(function () use (
                $request, $cid, $data, $billNumber, $status, $amountStr, $timestamp, $remark
            ) {
                /** @var DepositRequest|null $dep */
                $dep = DepositRequest::query()
                    // In your DepositController you send bill_number = $ref (reference/out_trade_no)
                    ->where('reference', $billNumber)
                    ->orWhere('out_trade_no', $billNumber)
                    ->lockForUpdate()
                    ->latest()
                    ->first();

                if (!$dep) {
                    Log::channel('winpay_daily')->warning('WINPAY notify: deposit not found', [
                        'cid' => $cid,
                        'bill_number' => $billNumber,
                        'status' => $status,
                        'amount' => $amountStr,
                    ]);
                    return;
                }

                // Store raw provider payload
                $dep->provider = 'winpay';
                $dep->provider_payload = $data;

                // Map WinPay status: 等待 / 待确认 / 已完成 / 失败
                if ($status === '失败') {
                    $dep->status = defined(DepositRequest::class.'::STATUS_FAILED')
                        ? DepositRequest::STATUS_FAILED
                        : 'failed';
                    $dep->save();
                    return;
                }

                if ($status !== '已完成') {
                    $dep->status = defined(DepositRequest::class.'::STATUS_PENDING')
                        ? DepositRequest::STATUS_PENDING
                        : 'pending';
                    $dep->save();
                    return;
                }

                // PAID: if already processed, do nothing (idempotent)
                $approvedConst = defined(DepositRequest::class.'::STATUS_APPROVED')
                    ? DepositRequest::STATUS_APPROVED
                    : 'approved';

                if ((string) $dep->status === (string) $approvedConst) {
                    $dep->save();
                    return;
                }

                // Mark deposit paid/processed
                $dep->status = $approvedConst;
                if (property_exists($dep, 'paid_at') || array_key_exists('paid_at', $dep->getAttributes())) {
                    $dep->paid_at = now();
                }
                if (property_exists($dep, 'processed_at') || array_key_exists('processed_at', $dep->getAttributes())) {
                    $dep->processed_at = now();
                }

                // Promotion handling (same logic as your VPAY sample)
                if (!empty($dep->promotion_id)) {
                    $promo = Promotion::query()->find($dep->promotion_id);

                    if ($promo && (bool) $promo->is_active) {
                        $depositAmt = (float) $dep->amount;

                        $bonus = 0.0;
                        if ($promo->bonus_type === 'fixed') {
                            $bonus = (float) $promo->bonus_value;
                        } else {
                            $bonus = ($depositAmt * (float) $promo->bonus_value) / 100.0;
                        }

                        if ($promo->bonus_cap !== null && $bonus > (float) $promo->bonus_cap) {
                            $bonus = (float) $promo->bonus_cap;
                        }

                        if ($promo->min_amount !== null && $depositAmt < (float) $promo->min_amount) {
                            $bonus = 0.0;
                        }
                        if ($promo->max_amount !== null && $depositAmt > (float) $promo->max_amount) {
                            $bonus = 0.0;
                        }

                        $bonus = round($bonus, 2);

                        $turn = (float) $promo->turnover_multiplier;
                        if ($turn <= 0) $turn = 1;

                        $required = round(($depositAmt + $bonus) * $turn, 2);

                        if (property_exists($dep, 'bonus_amount') || array_key_exists('bonus_amount', $dep->getAttributes())) {
                            $dep->bonus_amount = $bonus;
                        }
                        if (property_exists($dep, 'turnover_required') || array_key_exists('turnover_required', $dep->getAttributes())) {
                            $dep->turnover_required = $required;
                        }
                        if (property_exists($dep, 'turnover_progress') || array_key_exists('turnover_progress', $dep->getAttributes())) {
                            $dep->turnover_progress = $dep->turnover_progress ?? 0;
                        }
                        if (property_exists($dep, 'bonus_status') || array_key_exists('bonus_status', $dep->getAttributes())) {
                            $dep->bonus_status = $bonus > 0 ? 'in_progress' : 'none';
                        }
                    }
                }

                $dep->save();

                // CREDIT wallet main
                /** @var Wallet $wallet */
                $wallet = Wallet::query()
                    ->where('user_id', $dep->user_id)
                    ->where('type', Wallet::TYPE_MAIN)
                    ->lockForUpdate()
                    ->firstOrFail();

                $before = (float) $wallet->balance;
                $credit = (float) $dep->amount;
                $after  = $before + $credit;

                $wallet->balance = $after;
                $wallet->save();

                // WalletTransaction (idempotent guard: unique reference+provider)
                // If you already have a unique index, keep it; otherwise we do a check here.
                $exists = WalletTransaction::query()
                    ->where('user_id', $dep->user_id)
                    ->where('reference', $dep->reference)
                    ->where('title', 'Deposit (WinPay)')
                    ->exists();

                if (!$exists) {
                    WalletTransaction::create([
                        'user_id' => $dep->user_id,
                        'wallet_id' => $wallet->id,
                        'wallet_type' => $wallet->type,
                        'direction' => WalletTransaction::DIR_CREDIT,
                        'amount' => $credit,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'status' => WalletTransaction::STATUS_COMPLETED,
                        'reference' => $dep->reference,
                        'external_id' => $billNumber, // winpay bill_number from callback
                        'title' => 'Deposit (WinPay)',
                        'description' => 'WinPay payment success',
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'meta' => [
                            'provider' => 'winpay',
                            'winpay_status' => $status,
                            'winpay_timestamp' => $timestamp,
                            'remark' => $remark,
                        ],
                        'occurred_at' => now(),
                    ]);
                }

                Log::channel('winpay_daily')->info('WINPAY notify: credited wallet', [
                    'cid' => $cid,
                    'deposit_id' => $dep->id,
                    'user_id' => $dep->user_id,
                    'amount' => $credit,
                    'wallet_before' => $before,
                    'wallet_after' => $after,
                    'bill_number' => $billNumber,
                ]);
            });

            // WinPay expects OK
            return response('OK', 200);

        } catch (\Throwable $e) {
            Log::channel('winpay_daily')->error('WINPAY notify: exception', [
                'cid' => $cid,
                'err' => $e->getMessage(),
                'ex' => get_class($e),
                'trace_first_2000' => mb_substr($e->getTraceAsString(), 0, 2000),
            ]);

            // return 500 so WinPay retries (doc says up to 66 times)
            return response('ERR', 500);
        }
    }

    private function mask(?string $s, int $show = 4): ?string
    {
        if ($s === null) return null;
        $s = (string)$s;
        $len = strlen($s);
        if ($len <= $show * 2) return str_repeat('*', $len);
        return substr($s, 0, $show) . str_repeat('*', $len - ($show * 2)) . substr($s, -$show);
    }

    private function maskArray(array $arr): array
    {
        $sensitiveKeys = ['sign', 'api_key', 'key', 'secret', 'token', 'password', 'authorization'];

        $out = [];
        foreach ($arr as $k => $v) {
            $lk = strtolower((string)$k);

            if (in_array($lk, $sensitiveKeys, true)) {
                $out[$k] = is_string($v) ? $this->mask($v, 4) : '***';
                continue;
            }

            if (is_array($v)) {
                $out[$k] = $this->maskArray($v);
                continue;
            }

            $out[$k] = $v;
        }
        return $out;
    }
}
