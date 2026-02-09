<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\VipTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function show(Request $request)
    {
        // Your register UI is on home modal, so redirect home and keep ref.
        $ref = $request->query('ref');

        return redirect()->route('home', array_filter([
            'ref' => $ref,
        ]));
    }

    public function store(Request $request)
    {
        $request->merge([
            'otp' => $request->input('otp', '000000'),
            'email_otp' => $request->input('email_otp', '000000'),
        ]);
    
        // If URL has ?ref=xxxx but referral_code empty, use it
        if (!$request->filled('referral_code') && $request->filled('ref')) {
            $request->merge(['referral_code' => $request->input('ref')]);
        }
    
        // ✅ Default referral if still empty (no link, user didn't type)
        if (!$request->filled('referral_code')) {
            $request->merge(['referral_code' => config('app.default_referral_code', 'LMH')]);
        }
    
        $data = $request->validate([
            'username' => ['required','string','min:3','max:50','regex:/^[a-zA-Z0-9_]+$/', Rule::unique('users','username')],
            'email' => ['nullable','email','max:191', Rule::unique('users','email')],
            'phone_country' => ['nullable','string','max:6'],
            'phone' => ['nullable','string','max:32', Rule::unique('users','phone')],
            'password' => ['required','string','min:8','max:72','confirmed'],
            'referral_code' => ['nullable','string','max:32'],
            'country' => ['nullable','string','size:2'],
            'currency' => ['nullable','string','size:3'],
            'otp' => ['nullable','string','max:16'],
            'email_otp' => ['nullable','string','max:16'],
            'otp_type' => ['nullable','in:mobile,email'],
        ]);
    
        $user = DB::transaction(function () use ($data) {
            $referrerId = null;
    
            // ✅ resolve referrer id (LMH -> user id 4 in your case)
            if (!empty($data['referral_code'])) {
                $referrerId = User::where('referral_code', $data['referral_code'])->value('id');
            }
    
            $newReferral = strtoupper(substr($data['username'], 0, 3)).'-'.strtoupper(bin2hex(random_bytes(3)));
    
            $playerTierId = VipTier::firstOrCreate(
                ['level' => 0],
                ['name' => 'Player']
            )->id;
    
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'phone_country' => $data['phone_country'] ?? null,
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'currency' => $data['currency'] ?? 'MYR',
                'referral_code' => $newReferral,
                'referred_by_user_id' => $referrerId, // will become 4 if LMH exists
                'vip_tier_id' => $playerTierId,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
    
            return $user;
        });
    
        auth()->login($user);
    
        return redirect()->route('home')->with('success', 'Account created.');
    }

}
