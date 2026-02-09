<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\VPayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VPayNotifyController extends Controller
{
    public function handle(Request $request)
    {
        // 1) IP whitelist
        $allowedIp = config('services.vpay.callback_ip');
        $ip = $request->ip();

        if ($allowedIp && $ip !== $allowedIp) {
            Log::channel('deposit_daily')->warning('VPAY notify blocked by IP', [
                'ip' => $ip,
                'allowed' => $allowedIp,
            ]);
            return response('FAIL', 200);
        }

        // 2) must be JSON
        $body = $request->all();

        // 3) sign verification
        $sign = (string) ($body['sign'] ?? '');
        unset($body['sign']);

        $t = (int) $request->query('t', 0); // they said query contains unix timestamp
        if ($t <= 0 || $sign === '') {
            Log::channel('deposit_daily')->warning('VPAY notify missing t/sign', [
                'query_t' => $request->query('t'),
                'has_sign' => $sign !== '',
            ]);
            return response('FAIL', 200);
        }

        $client = VPayClient::make();
        if (!$client->verifySign($body, $t, $sign)) {
            Log::channel('deposit_daily')->warning('VPAY notify signature invalid', [
                't' => $t,
                'ip' => $ip,
                'body' => $request->all(),
            ]);
            return response('FAIL', 200);
        }

        // 4) interpret fields (based on doc: state 3=Paid)
        $tradeNo = (string) ($body['trade_no'] ?? '');
        $outTradeNo = (string) ($body['out_trade_no'] ?? '');
        $state = (int) ($body['state'] ?? -1);
        $amount = (string) ($body['amount'] ?? '0');

        if ($tradeNo === '' && $outTradeNo === '') {
            Log::channel('deposit_daily')->warning('VPAY notify missing identifiers', [
                'body' => $request->all(),
            ]);
            return response('FAIL', 200);
        }

        // 5) idempotent crediting
        DB::transaction(function () use ($tradeNo, $outTradeNo, $state, $amount, $request) {

            $dep = DepositRequest::query()
                ->when($tradeNo !== '', fn($q) => $q->where('trade_no', $tradeNo))
                ->when($tradeNo === '' && $outTradeNo !== '', fn($q) => $q->where('out_trade_no', $outTradeNo))
                ->lockForUpdate()
                ->first();

            if (!$dep) {
                Log::channel('deposit_daily')->warning('VPAY notify deposit not found', [
                    'trade_no' => $tradeNo,
                    'out_trade_no' => $outTradeNo,
                ]);
                return;
            }

            // store raw payload
            $dep->provider_payload = $request->all();

            // Paid
            if ($state === 3) {
                if ($dep->status === DepositRequest::STATUS_APPROVED) {
                    // already credited
                    $dep->save();
                    return;
                }

                $dep->status = DepositRequest::STATUS_APPROVED;
                $dep->paid_at = now();
                $dep->processed_at = now();
                $dep->save();

                // CREDIT wallet main
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
                    'external_id' => $dep->trade_no ?: $dep->out_trade_no,
                    'title' => 'Deposit (VPay)',
                    'description' => 'VPay payment success',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'meta' => [
                        'provider' => 'vpay',
                        'trade_no' => $dep->trade_no,
                        'out_trade_no' => $dep->out_trade_no,
                    ],
                    'occurred_at' => now(),
                ]);

                return;
            }

            // Not paid: keep pending / or mark rejected based on states you want
            $dep->save();
        });

        // Must return "SUCCESS"
        return response('SUCCESS', 200);
    }
}