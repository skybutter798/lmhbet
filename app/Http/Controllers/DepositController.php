<?php

namespace App\Http\Controllers;

use App\Models\DepositRequest;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

use App\Services\VPayClient;
use App\Services\Payments\WinPayClient;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $wallets = $user->wallets()
            ->whereIn('type', ['main', 'chips', 'bonus'])
            ->get()
            ->keyBy('type');

        $cash  = $wallets->get('main')?->balance ?? 0;
        $chips = $wallets->get('chips')?->balance ?? 0;
        $bonus = $wallets->get('bonus')?->balance ?? 0;

        $banks = [
            'Maybank',
            'CIMB Bank',
            'Public Bank',
            'Hong Leong Bank',
            'RHB Bank',
            'AmBank',
            'Affin Bank',
            'Alliance Bank',
            'Bank Islam',
            'Bank Rakyat',
            'HSBC Bank',
            'UOB Bank',
            'OCBC Bank',
            'Standard Chartered',
        ];

        $winpayFpxBanks = [
            'Public Bank',
            'Bank Rakyat',
            'Alliance Bank',
            'Maybank2U',
            'Bank Islam',
            'Bank Muamalat',
            'Kuwait Finance House',
            'Affin Bank',
            'RHB Bank',
            'OCBC Bank',
            'Standard Chartered',
            'Hong Leong Bank',
            'Maybank2E',
            'UOB Bank',
            'CIMB Clicks',
            'AmBank',
            'Bank Simpanan Nasional',
            'HSBC Bank',
            'Bank of China',
            'Bank Pertanian Malaysia Berhad (Agrobank)',
        ];

        $winpayEwallets = [
            'Boost',
            'GrabPay',
            'Touch N Go',
        ];

        // ✅ VPAY trade_code list (for UI)
        $vpayTradeCodes = [
            '36' => 'DUITNOW',
            '24' => 'P2P Online Banking',
            '40' => 'TNG',
        ];

        $today = now()->startOfDay();
        $history = $user->depositRequests()
            ->where('created_at', '>=', $today)
            ->latest()
            ->take(20)
            ->get();

        $currency = $user->currency ?? 'MYR';
        $now = Carbon::now();

        $promotions = Promotion::query()
            ->where('is_active', true)
            ->where(function ($q) use ($currency) {
                $q->whereNull('currency')->orWhere('currency', $currency);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->with(['dboxProviders:id,name'])
            ->orderBy('sort_order')
            ->get();

        return view('deposits.index', [
            'title'      => 'Deposit',
            'currency'   => $currency,
            'cash'       => $cash,
            'chips'      => $chips,
            'bonus'      => $bonus,
            'banks'      => $banks,
            'history'    => $history,
            'promotions' => $promotions,

            'winpayFpxBanks' => $winpayFpxBanks,
            'winpayEwallets' => $winpayEwallets,

            // ✅ pass to blade
            'vpayTradeCodes' => $vpayTradeCodes,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'method'       => ['required', 'string', 'in:bank_transfer,e_wallet'],
            'provider'     => ['nullable', 'string', 'in:vpay,winpay'],
            'winpay_type'  => ['nullable', 'string', 'in:01,03'],
            'bank_name'    => ['nullable', 'string', 'max:80'],
            'amount'       => ['required', 'numeric', 'min:20', 'max:20000'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],

            // ✅ NEW: vpay channel selector
            'trade_code'   => ['nullable', 'string', 'in:24,36,37,38,40'],
        ]);

        $provider = $data['provider'] ?? 'vpay';

        if ($data['method'] === DepositRequest::METHOD_BANK_TRANSFER && empty($data['bank_name'])) {
            return back()->withErrors(['bank_name' => 'Please select a bank.'])->withInput();
        }

        if ($data['method'] === DepositRequest::METHOD_E_WALLET) {
            if ($provider === 'winpay') {
                if (empty($data['winpay_type'])) {
                    return back()->withErrors(['method' => 'Please select WinPay type.'])->withInput();
                }
                if (empty($data['bank_name'])) {
                    return back()->withErrors(['bank_name' => 'Please select a bank / wallet.'])->withInput();
                }
            }

            // ✅ NEW: if provider=vpay, ensure trade_code exists (default allowed)
            if ($provider === 'vpay') {
                $data['trade_code'] = (string)($data['trade_code'] ?? '36');
                $allowed = ['24','36','37','38','40'];
                if (!in_array($data['trade_code'], $allowed, true)) {
                    return back()->withErrors(['amount' => 'Invalid VPAY channel selected.'])->withInput();
                }
            }
        }

        $cid = 'dep_' . now()->format('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12);

        Log::channel('deposit_daily')->info('Deposit store: request received', [
            'cid' => $cid,
            'user_id' => $user->id,
            'method' => $data['method'],
            'provider' => $provider,
            'winpay_type' => $data['winpay_type'] ?? null,
            'trade_code' => $data['trade_code'] ?? null, // ✅ log it
            'amount' => $data['amount'],
            'currency' => $user->currency ?? 'MYR',
            'bank_name' => $data['bank_name'] ?? null,
            'promotion_id' => $data['promotion_id'] ?? null,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        $ref = 'TP_WLA_' . now()->format('ymdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);

        $payload = [
            'currency'   => $user->currency ?? 'MYR',
            'method'     => $data['method'],
            'bank_name'  => $data['method'] === DepositRequest::METHOD_BANK_TRANSFER
                ? ($data['bank_name'] ?? null)
                : ($data['bank_name'] ?? null),
            'amount'     => $data['amount'],
            'status'     => DepositRequest::STATUS_PENDING,
            'reference'  => $ref,
        ];

        if (Schema::hasColumn('deposit_requests', 'promotion_id')) {
            $payload['promotion_id'] = $data['promotion_id'] ?? null;
        }

        // ✅ NEW: store chosen VPAY trade_code when provider=vpay
        if ($provider === 'vpay' && Schema::hasColumn('deposit_requests', 'trade_code')) {
            $payload['trade_code'] = (string)($data['trade_code'] ?? '36');
        }

        /** @var \App\Models\DepositRequest $dep */
        $dep = $user->depositRequests()->create($payload);

        Log::channel('deposit_daily')->info('Deposit created', [
            'cid' => $cid,
            'deposit_id' => $dep->id,
            'reference' => $ref,
            'status' => $dep->status,
            'method' => $dep->method,
            'amount' => $dep->amount,
            'currency' => $dep->currency,
        ]);

        if ($data['method'] === DepositRequest::METHOD_BANK_TRANSFER) {
            Log::channel('deposit_daily')->info('Deposit bank_transfer: submitted', [
                'cid' => $cid,
                'deposit_id' => $dep->id,
                'reference' => $ref,
                'bank_name' => $dep->bank_name,
            ]);

            return back()->with('success', 'Deposit request submitted. Status: In Progress.');
        }

        // =========================
        // E-WALLET (WinPay / VPay)
        // =========================

        if ($provider === 'winpay') {
            try {
                /** @var WinPayClient $winpay */
                $winpay = app(WinPayClient::class);

                $type = (string) ($data['winpay_type'] ?? '01');

                $limits = (array) config('winpay.limits', []);
                if (isset($limits[$type])) {
                    $min = (float) $limits[$type]['min'];
                    $max = (float) $limits[$type]['max'];
                    if ((float) $data['amount'] < $min || (float) $data['amount'] > $max) {
                        return back()->withErrors([
                            'amount' => "Amount must be between {$min} and {$max} for WinPay type {$type}.",
                        ])->withInput();
                    }
                }

                $depositorName = strtoupper($user->name ?? 'CUSTOMER');

                $winPayload = [
                    'bill_number' => $ref,
                    'type' => $type,
                    'amount' => (float) $data['amount'],
                    'depositor_name' => $depositorName,
                    'bank_name' => (string) ($data['bank_name'] ?? ''),
                ];

                Log::channel('deposit_daily')->info('WINPAY deposit: sending', [
                    'cid' => $cid,
                    'deposit_id' => $dep->id,
                    'ref' => $ref,
                    'payload' => $winPayload,
                ]);

                $resp = $winpay->createDeposit($winPayload);

                Log::channel('deposit_daily')->info('WINPAY deposit: response', [
                    'cid' => $cid,
                    'deposit_id' => $dep->id,
                    'ref' => $ref,
                    'resp_code' => $resp['code'] ?? null,
                    'resp_msg' => $resp['message'] ?? null,
                    'resp' => $resp,
                ]);

                if ((int) ($resp['code'] ?? -1) !== 0) {
                    Log::channel('deposit_daily')->warning('WINPAY deposit failed', [
                        'cid' => $cid,
                        'deposit_id' => $dep->id,
                        'ref' => $ref,
                        'user_id' => $user->id,
                        'resp' => $resp,
                    ]);

                    return back()->withErrors([
                        'amount' => 'WinPay gateway error: ' . (string) ($resp['message'] ?? 'Unknown'),
                    ])->withInput();
                }

                $dep->provider = 'winpay';
                $dep->out_trade_no = $ref;
                $dep->trade_no = $resp['trade_no'] ?? null;
                $dep->pay_url = $resp['url'] ?? null;
                $dep->trade_code = $type; // 01/03
                $dep->provider_payload = $resp;
                $dep->save();

                if (!$dep->pay_url) {
                    Log::channel('deposit_daily')->warning('WINPAY response missing pay_url', [
                        'cid' => $cid,
                        'deposit_id' => $dep->id,
                        'ref' => $ref,
                        'resp' => $resp,
                    ]);

                    return back()->withErrors(['amount' => 'WinPay payment url missing.'])->withInput();
                }

                Log::channel('deposit_daily')->info('Redirecting user to WINPAY pay_url', [
                    'cid' => $cid,
                    'deposit_id' => $dep->id,
                    'ref' => $ref,
                    'pay_url' => $dep->pay_url,
                ]);

                return redirect()->away($dep->pay_url);

            } catch (\Throwable $e) {
                Log::channel('deposit_daily')->error('WINPAY deposit exception', [
                    'cid' => $cid,
                    'deposit_id' => $dep->id ?? null,
                    'ref' => $ref ?? null,
                    'user_id' => $user->id,
                    'err' => $e->getMessage(),
                    'ex' => get_class($e),
                ]);

                return back()->withErrors(['amount' => 'WinPay gateway exception. Please try again.'])->withInput();
            }
        }

        // default VPAY
        try {
            $client = VPayClient::make();

            // ✅ use requested trade_code (default 36)
            $tradeCode = (string)($data['trade_code'] ?? $dep->trade_code ?? '36');

            $vpayPayload = [
                'title'        => 'Deposit',
                'out_trade_no' => $ref,
                'amount'       => number_format((float)$data['amount'], 2, '.', ''),
                'trade_code'   => $tradeCode,
                'payer_name'   => strtoupper($user->name ?? 'CUSTOMER'),
                'notify_url'   => config('services.vpay.notify_url'),
                'callback_url' => config('services.vpay.callback_url'),
            ];

            Log::channel('deposit_daily')->info('VPAY unifiedOrder: sending', [
                'cid' => $cid,
                'deposit_id' => $dep->id,
                'ref' => $ref,
                'payload' => $vpayPayload,
            ]);

            $resp = $client->unifiedOrder($vpayPayload);

            Log::channel('deposit_daily')->info('VPAY unifiedOrder: response', [
                'cid' => $cid,
                'deposit_id' => $dep->id,
                'ref' => $ref,
                'resp_code' => $resp['code'] ?? null,
                'resp_msg' => $resp['msg'] ?? null,
                'resp' => $resp,
            ]);

            if (($resp['code'] ?? -1) != 0) {
                Log::channel('deposit_daily')->warning('VPAY unified order failed', [
                    'cid' => $cid,
                    'deposit_id' => $dep->id,
                    'ref' => $ref,
                    'user_id' => $user->id,
                    'resp' => $resp,
                ]);

                return back()->withErrors([
                    'amount' => 'Payment gateway error: ' . ($resp['msg'] ?? 'Unknown'),
                ])->withInput();
            }

            $dataObj = $resp['data'] ?? [];

            $dep->provider = 'vpay';
            $dep->out_trade_no = $ref;
            $dep->trade_no = $dataObj['trade_no'] ?? null;
            $dep->pay_url = $dataObj['pay_url'] ?? null;
            $dep->trade_code = $tradeCode; // ✅ keep the selected one
            $dep->provider_payload = $resp;
            $dep->save();

            Log::channel('deposit_daily')->info('Deposit updated with VPAY fields', [
                'cid' => $cid,
                'deposit_id' => $dep->id,
                'ref' => $ref,
                'trade_no' => $dep->trade_no,
                'pay_url_present' => (bool) $dep->pay_url,
                'trade_code' => $dep->trade_code,
            ]);

            if (!$dep->pay_url) {
                Log::channel('deposit_daily')->warning('VPAY response missing pay_url', [
                    'cid' => $cid,
                    'deposit_id' => $dep->id,
                    'ref' => $ref,
                    'data' => $dataObj,
                ]);

                return back()->withErrors(['amount' => 'Payment URL missing from gateway response.'])->withInput();
            }

            Log::channel('deposit_daily')->info('Redirecting user to VPAY pay_url', [
                'cid' => $cid,
                'deposit_id' => $dep->id,
                'ref' => $ref,
                'pay_url' => $dep->pay_url,
            ]);

            return redirect()->away($dep->pay_url);

        } catch (\Throwable $e) {
            Log::channel('deposit_daily')->error('VPAY unified order exception', [
                'cid' => $cid,
                'deposit_id' => $dep->id ?? null,
                'ref' => $ref ?? null,
                'user_id' => $user->id,
                'err' => $e->getMessage(),
                'ex' => get_class($e),
            ]);

            return back()->withErrors(['amount' => 'Payment gateway exception. Please try again.'])->withInput();
        }
    }
}
