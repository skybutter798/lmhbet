<?php

namespace App\Http\Controllers;

use App\Models\DepositRequest;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

use App\Services\VPayClient;
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
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
    
        $data = $request->validate([
            'method'       => ['required', 'string', 'in:bank_transfer,e_wallet'],
            'bank_name'    => ['nullable', 'string', 'max:80'],
            'amount'       => ['required', 'numeric', 'min:20', 'max:20000'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
        ]);
    
        if ($data['method'] === DepositRequest::METHOD_BANK_TRANSFER && empty($data['bank_name'])) {
            return back()->withErrors(['bank_name' => 'Please select a bank.'])->withInput();
        }
    
        // your internal reference
        $ref = 'TP_WLA_' . now()->format('ymdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
    
        // create deposit request first
        $payload = [
            'currency'   => $user->currency ?? 'MYR',
            'method'     => $data['method'],
            'bank_name'  => $data['method'] === DepositRequest::METHOD_BANK_TRANSFER ? $data['bank_name'] : null,
            'amount'     => $data['amount'],
            'status'     => DepositRequest::STATUS_PENDING,
            'reference'  => $ref,
        ];
    
        if (Schema::hasColumn('deposit_requests', 'promotion_id')) {
            $payload['promotion_id'] = $data['promotion_id'] ?? null;
        }
    
        /** @var \App\Models\DepositRequest $dep */
        $dep = $user->depositRequests()->create($payload);
    
        // bank transfer = done
        if ($data['method'] === DepositRequest::METHOD_BANK_TRANSFER) {
            return back()->with('success', 'Deposit request submitted. Status: In Progress.');
        }
    
        // e_wallet => create VPay order
        try {
            $client = VPayClient::make();
    
            // choose a trade_code. Example: 36 = DUITNOW
            $tradeCode = '36';
    
            $resp = $client->unifiedOrder([
                'title'        => 'Deposit',
                'out_trade_no' => $ref,
                'amount'       => number_format((float)$data['amount'], 2, '.', ''),
                'trade_code'   => $tradeCode,
                'payer_name'   => strtoupper($user->name ?? 'CUSTOMER'),
                'notify_url'   => config('services.vpay.notify_url'),
                'callback_url' => config('services.vpay.callback_url'),
            ]);
    
            if (($resp['code'] ?? -1) != 0) {
                Log::channel('deposit_daily')->warning('VPAY unified order failed', [
                    'resp' => $resp,
                    'ref' => $ref,
                    'user_id' => $user->id,
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
            $dep->trade_code = $tradeCode;
            $dep->provider_payload = $resp;
            $dep->save();
    
            if (!$dep->pay_url) {
                return back()->withErrors(['amount' => 'Payment URL missing from gateway response.'])->withInput();
            }
    
            // Redirect user to cashier/payment URL
            return redirect()->away($dep->pay_url);
    
        } catch (\Throwable $e) {
            Log::channel('deposit_daily')->error('VPAY unified order exception', [
                'err' => $e->getMessage(),
                'ref' => $ref,
                'user_id' => $user->id,
            ]);
    
            return back()->withErrors(['amount' => 'Payment gateway exception. Please try again.'])->withInput();
        }
    }
}
