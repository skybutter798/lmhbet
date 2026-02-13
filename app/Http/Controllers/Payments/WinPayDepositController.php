<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\WinPayDeposit;
use App\Services\Payments\WinPayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WinPayDepositController extends Controller
{
    public function create(Request $request, WinPayClient $winpay)
    {
        $user = Auth::user();

        $type = (string) $request->input('type', '01'); // 01 FPX/Bank, 03 EWallet
        $amount = (float) $request->input('amount', 0);
        $depositorName = (string) $request->input('depositor_name', '');
        $bankName = (string) $request->input('bank_name', '');

        // basic validate (按你给的限制)
        $limits = (array) config('winpay.limits', []);
        if (isset($limits[$type])) {
            $min = (float) $limits[$type]['min'];
            $max = (float) $limits[$type]['max'];
            if ($amount < $min || $amount > $max) {
                return back()->withErrors([
                    'amount' => "Amount must be between {$min} and {$max} for type {$type}",
                ]);
            }
        } else {
            if ($amount <= 0) {
                return back()->withErrors(['amount' => 'Invalid amount']);
            }
        }

        if (trim($depositorName) === '') {
            return back()->withErrors(['depositor_name' => 'Depositor name required']);
        }

        if (trim($bankName) === '') {
            return back()->withErrors(['bank_name' => 'Bank / Wallet name required']);
        }

        $billNumber = $this->makeBillNumber($user->id);

        $deposit = WinPayDeposit::create([
            'user_id' => $user->id,
            'bill_number' => $billNumber,
            'type' => $type,
            'bank_name' => $bankName,
            'depositor_name' => $depositorName,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $resp = $winpay->createDeposit([
            'bill_number' => $billNumber,
            'type' => $type,
            'amount' => $amount,
            'depositor_name' => $depositorName,
            'bank_name' => $bankName,
        ]);

        $deposit->request_payload = [
            'bill_number' => $billNumber,
            'type' => $type,
            'amount' => $amount,
            'depositor_name' => $depositorName,
            'bank_name' => $bankName,
        ];
        $deposit->create_response = $resp;

        // 文档：code 非 0 表示订单产生失败（不是最终状态）:contentReference[oaicite:2]{index=2}
        if ((int) ($resp['code'] ?? -1) !== 0) {
            $deposit->status = 'failed';
            $deposit->save();

            return back()->withErrors([
                'winpay' => 'WinPay create failed: ' . (string) ($resp['message'] ?? 'unknown'),
            ]);
        }

        $payUrl = (string) ($resp['url'] ?? '');
        $deposit->pay_url = $payUrl;
        $deposit->winpay_status = (string) ($resp['status'] ?? null);
        $deposit->save();

        if ($payUrl !== '') {
            return redirect()->away($payUrl);
        }

        return back()->withErrors(['winpay' => 'No pay url returned']);
    }

    public function query(string $billNumber, WinPayClient $winpay)
    {
        $deposit = WinPayDeposit::where('bill_number', $billNumber)->firstOrFail();

        // optional: only owner can query
        if ((int) $deposit->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $resp = $winpay->queryDeposit($billNumber);

        // 如果查询返回最终状态就更新
        if ((int) ($resp['code'] ?? -1) === 0) {
            $winStatus = (string) ($resp['status'] ?? '');
            $deposit->winpay_status = $winStatus;

            if ($winStatus === '已完成') {
                $deposit->status = 'paid';
                $deposit->paid_at = now();
            } elseif ($winStatus === '失败') {
                $deposit->status = 'failed';
            } else {
                $deposit->status = 'pending';
            }

            $deposit->save();
        }

        return response()->json([
            'ok' => true,
            'bill_number' => $billNumber,
            'local_status' => $deposit->status,
            'winpay_status' => $deposit->winpay_status,
            'response' => $resp,
        ]);
    }

    private function makeBillNumber(int $userId): string
    {
        // 保证唯一性：WP + 时间 + user + 随机
        return 'WP' . now()->format('YmdHis') . '_' . $userId . '_' . Str::lower(Str::random(6));
    }
}
