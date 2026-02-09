<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // Optional: block inactive admins
        $admin = Auth::guard('admin')->user();
        if (!$admin->is_active) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')->withErrors([
                'username' => 'Account is inactive.',
            ]);
        }

        return $next($request);
    }
}
