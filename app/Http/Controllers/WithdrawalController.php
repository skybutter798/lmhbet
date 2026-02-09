<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\WithdrawalRequest as WithdrawalRequestModel;
use App\Models\Wallet;

class WithdrawalController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $wallets = $user->wallets()
            ->whereIn('type', ['main', 'chips', 'bonus'])
            ->get()
            ->keyBy('type');

        $cash  = $wallets->get('main')?->balance ?? 0;
        $chips = $wallets->get('chips')?->balance ?? 0;
        $bonus = $wallets->get('bonus')?->balance ?? 0;

        $currency = $user->currency ?? 'MYR';

        $verifiedKyc = $user->kycSubmissions()
            ->whereIn('status', ['approved', 'success'])
            ->latest()
            ->get();

        $today = now()->startOfDay();

        $todayHistory = WithdrawalRequestModel::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $today)
            ->latest()
            ->get();

        return view('withdrawals.index', [
            'title' => 'Withdrawal',

            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,
            'currency' => $currency,

            'verifiedKyc' => $verifiedKyc,
            'todayHistory' => $todayHistory,

            'minWithdraw' => 100,
            'maxWithdraw' => 99999999.99,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $currency = $user->currency ?? 'MYR';

        // Normalize amount input (remove commas/spaces)
        $request->merge([
            'amount' => is_string($request->input('amount'))
                ? str_replace([',', ' '], '', $request->input('amount'))
                : $request->input('amount'),
        ]);

        $data = $request->validate([
            'kyc_submission_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:100', 'max:99999999.99'],
        ]);

        // Must be verified KYC
        $kyc = $user->kycSubmissions()
            ->whereIn('status', ['approved', 'success'])
            ->where('id', $data['kyc_submission_id'])
            ->first();

        if (!$kyc) {
            return back()
                ->withErrors(['withdraw' => 'No verified bank account found. Please complete verification first.'])
                ->withInput();
        }

        $amountStr = (string) $data['amount'];
        $amountCents = $this->moneyToCents($amountStr);

        if ($amountCents <= 0) {
            return back()
                ->withErrors(['amount' => 'Invalid amount.'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($user, $currency, $kyc, $amountCents, $amountStr) {

                // LOCK cash wallet row (ONLY main/cash is withdrawable)
                $cashWallet = $user->wallets()
                    ->where('type', Wallet::TYPE_MAIN)
                    ->lockForUpdate()
                    ->first();

                if (!$cashWallet) {
                    throw new \RuntimeException('Cash wallet not found.');
                }

                $balanceCents = $this->moneyToCents((string) $cashWallet->balance);

                if ($amountCents > $balanceCents) {
                    throw new \RuntimeException('Insufficient cash balance.');
                }

                // Deduct immediately (hold in pending)
                $newBalanceCents = $balanceCents - $amountCents;
                $cashWallet->balance = $this->centsToMoney($newBalanceCents);
                $cashWallet->save();

                WithdrawalRequestModel::create([
                    'user_id' => $user->id,
                    'kyc_submission_id' => $kyc->id,
                    'currency' => $currency,
                    'amount' => $amountStr,   // stored as decimal:2 by casts
                    'status' => 'pending',
                    'remarks' => null,
                ]);
            }, 3); // retry up to 3 times on deadlock
        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            if ($msg === 'Insufficient cash balance.') {
                return back()
                    ->withErrors(['amount' => 'Insufficient cash balance.'])
                    ->withInput();
            }

            return back()
                ->withErrors(['withdraw' => $msg ?: 'Withdrawal failed. Please try again.'])
                ->withInput();
        }

        return back()->with('success', 'Withdrawal submitted.');
    }

    /**
     * Convert "1234.56" to cents (123456) without floats.
     */
    private function moneyToCents(string $v): int
    {
        $s = trim($v);
        $s = str_replace([',', ' '], '', $s);

        if ($s === '') return 0;

        $neg = false;
        if (str_starts_with($s, '-')) {
            $neg = true;
            $s = substr($s, 1);
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) {
            return 0;
        }

        [$whole, $dec] = array_pad(explode('.', $s, 2), 2, '0');
        $dec = substr(str_pad($dec, 2, '0'), 0, 2);

        $cents = ((int) $whole) * 100 + (int) $dec;
        return $neg ? -$cents : $cents;
    }

    /**
     * Convert cents (123456) back to "1234.56".
     */
    private function centsToMoney(int $cents): string
    {
        $neg = $cents < 0;
        $cents = abs($cents);

        $whole = intdiv($cents, 100);
        $dec = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);

        return ($neg ? '-' : '') . $whole . '.' . $dec;
    }
}
