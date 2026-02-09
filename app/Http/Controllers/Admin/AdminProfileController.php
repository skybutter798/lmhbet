<?php
// /home/lmh/app/app/Http/Controllers/Admin/AdminProfileController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    public function modal()
    {
        $admin = Auth::guard('admin')->user();
        return view('admins.partials.profile_modal', compact('admin'));
    }

    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'current_password' => ['required','string','min:6'],
            'new_password'     => ['required','string','min:8','confirmed'],
        ]);

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json(['ok' => false, 'message' => 'Current password wrong'], 422);
        }

        $admin->password = Hash::make($request->new_password);
        $admin->save();

        return response()->json(['ok' => true, 'message' => 'Password updated']);
    }

    public function updatePin(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'current_password' => ['required','string','min:6'],
            'new_pin'          => ['required','string','min:4','max:12','confirmed'],
        ]);

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json(['ok' => false, 'message' => 'Current password wrong'], 422);
        }

        $admin->pin = Hash::make($request->new_pin);
        $admin->save();

        return response()->json(['ok' => true, 'message' => 'PIN updated']);
    }

    public function updateTwoFaSecret(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'current_password' => ['required','string','min:6'],
            'two_fa_secret'    => ['nullable','string','max:255'],
            'action'           => ['required','in:set,disable'],
        ]);

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json(['ok' => false, 'message' => 'Current password wrong'], 422);
        }

        if ($request->action === 'disable') {
            $admin->two_fa_secret = null;
        } else {
            $admin->two_fa_secret = $request->two_fa_secret ?: null;
        }

        $admin->save();

        return response()->json(['ok' => true, 'message' => '2FA updated']);
    }
}
