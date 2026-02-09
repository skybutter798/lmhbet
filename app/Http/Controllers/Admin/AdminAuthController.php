<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admins.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'pin'      => ['required', 'string'],
        ]);

        $credentials = $request->only('username', 'password');

        // First check username/password
        if (!Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'username' => 'Invalid credentials.',
            ])->withInput();
        }

        $admin = Auth::guard('admin')->user();

        // Then verify pin (pin is hashed)
        if (!Hash::check($request->pin, $admin->pin)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'pin' => 'Invalid PIN.',
            ])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
