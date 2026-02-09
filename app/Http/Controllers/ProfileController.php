<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\KycSubmission;

class ProfileController extends Controller
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

        $currencyNameMap = [
            'MYR' => 'Malaysian Ringgit',
            'SGD' => 'Singapore Dollar',
            'USD' => 'US Dollar',
        ];

        $countryMap = [
            'MY' => 'Malaysia',
            'SG' => 'Singapore',
            'US' => 'United States',
        ];

        $referrer = $user->referrer?->username ?? null;

        $isKycTab = request()->boolean('kyc');
        $latestKyc = $user->kycSubmissions()->latest()->first();

        $banks = [
            'Affin Bank',
            'Agrobank',
            'Alliance Bank',
            'AmBank',
            'Bank Islam',
            'Bank Muamalat',
            'Bank Rakyat',
            'Bank Simpanan Nasional (BSN)',
            'CIMB Bank',
            'Citibank Malaysia',
            'Hong Leong Bank',
            'HSBC Bank Malaysia',
            'Kuwait Finance House',
            'Maybank',
            'OCBC Bank Malaysia',
            'Public Bank',
            'RHB Bank',
            'Standard Chartered Bank Malaysia',
            'UOB Malaysia',
        ];

        return view('profile.index', [
            'title' => 'My Profile',

            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,
            'total' => (float)$cash + (float)$chips + (float)$bonus,

            'currency' => $user->currency ?? 'MYR',
            'currencyName' => $currencyNameMap[$user->currency ?? 'MYR'] ?? ($user->currency ?? 'MYR'),
            'countryName' => $countryMap[$user->country ?? 'MY'] ?? ($user->country ?? '-'),

            'vipGroup' => $user->vipTier?->name ?? 'Player',
            'referrerMasked' => $referrer ? $this->maskMiddle($referrer) : '-',

            // ✅ KYC
            'isKycTab' => $isKycTab,
            'latestKyc' => $latestKyc,
            'banks' => $banks,
        ]);
    }

    public function submitKyc(Request $request)
    {
        $user = auth()->user();

        // block if already pending
        $hasPending = $user->kycSubmissions()->where('status', KycSubmission::STATUS_PENDING)->exists();
        if ($hasPending) {
            return back()->withErrors(['kyc' => 'You already have a verification request in progress.']);
        }

        $banks = [
            'Affin Bank','Agrobank','Alliance Bank','AmBank','Bank Islam','Bank Muamalat','Bank Rakyat',
            'Bank Simpanan Nasional (BSN)','CIMB Bank','Citibank Malaysia','Hong Leong Bank',
            'HSBC Bank Malaysia','Kuwait Finance House','Maybank','OCBC Bank Malaysia','Public Bank',
            'RHB Bank','Standard Chartered Bank Malaysia','UOB Malaysia',
        ];

        $data = $request->validate([
            'bank_name' => ['required', 'string', Rule::in($banks)],
            'account_holder_name' => ['required', 'string', 'max:191'],
            'account_number' => ['required', 'string', 'max:64'],
        ]);

        $user->kycSubmissions()->create([
            'bank_name' => $data['bank_name'],
            'account_holder_name' => $data['account_holder_name'],
            'account_number' => $data['account_number'],
            'status' => KycSubmission::STATUS_PENDING,
            'remarks' => null,
        ]);

        return redirect()->route('profile.index', ['kyc' => true])->with('success', 'KYC submitted.');
    }

    public function cancelKyc(Request $request)
    {
        $user = auth()->user();

        $kyc = $user->kycSubmissions()->latest()->first();
        if (!$kyc || $kyc->status !== KycSubmission::STATUS_PENDING) {
            return back()->withErrors(['kyc' => 'No pending verification to cancel.']);
        }

        $kyc->status = KycSubmission::STATUS_CANCELLED;
        $kyc->remarks = 'Cancelled by user';
        $kyc->save();

        return redirect()->route('profile.index', ['kyc' => true])->with('success', 'KYC cancelled.');
    }

    public function updateEmail(Request $request)
    {
        $user = auth()->user();

        // OTP placeholder (not enforced)
        $request->merge([
            'email_otp' => $request->input('email_otp', '000000'),
        ]);

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'email_otp' => ['nullable', 'string', 'max:16'],
        ]);

        $user->email = $data['email'];
        $user->save();

        return back()->with('success', 'Email updated.');
    }

    public function updatePhone(Request $request)
    {
        $user = auth()->user();

        // OTP placeholder (not enforced)
        $request->merge([
            'otp' => $request->input('otp', '000000'),
            'phone_country' => $request->input('phone_country', $user->phone_country ?? '+60'),
        ]);

        $data = $request->validate([
            'phone_country' => ['nullable', 'string', 'max:6'],
            'phone' => [
                'required',
                'string',
                'max:32',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'otp' => ['nullable', 'string', 'max:16'],
        ]);

        $user->phone_country = $data['phone_country'] ?: $user->phone_country;
        $user->phone = $data['phone'];
        $user->save();

        return back()->with('success', 'Phone updated.');
    }

    private function malaysiaBanks(): array
    {
        return [
            'Maybank',
            'CIMB Bank',
            'Public Bank',
            'RHB Bank',
            'Hong Leong Bank',
            'AmBank',
            'Bank Islam',
            'Bank Rakyat',
            'Alliance Bank',
            'Affin Bank',
            'UOB Malaysia',
            'OCBC Bank Malaysia',
            'HSBC Bank Malaysia',
            'Standard Chartered Bank Malaysia',
            'Citibank Malaysia',
            'Bank Muamalat',
            'Bank Simpanan Nasional (BSN)',
            'Agrobank',
            'Al Rajhi Bank',
            'MBSB Bank',
            'Kuwait Finance House (KFH)',
        ];
    }

    private function maskMiddle(string $value): string
    {
        $len = mb_strlen($value);
        if ($len <= 2) return $value;
        if ($len <= 4) return mb_substr($value, 0, 1) . str_repeat('*', $len - 2) . mb_substr($value, -1);
        return mb_substr($value, 0, 2) . str_repeat('*', $len - 3) . mb_substr($value, -1);
    }
}
