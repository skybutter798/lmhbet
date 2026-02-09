<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt(['username' => $data['username'], 'password' => $data['password']], $remember)) {
            $request->session()->regenerate();
            return redirect()->route('home');
        }

        return back()
            ->withErrors(['username' => 'Invalid username or password.'])
            ->withInput($request->only('username') + ['_form' => 'login']);
    }
}
