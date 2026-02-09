@extends('admins.layout')

@section('title', 'Admin Login')

@section('body')
  <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;">
    <div class="card" style="width:420px;">
      <div style="font-size:20px; font-weight:700; margin-bottom:12px;">Admin Login</div>

      <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf

        <label class="label">Username</label>
        <input class="input" name="username" value="{{ old('username') }}" autocomplete="username" />
        @error('username') <div class="err">{{ $message }}</div> @enderror

        <label class="label">Password</label>
        <input class="input" type="password" name="password" autocomplete="current-password" />
        @error('password') <div class="err">{{ $message }}</div> @enderror

        <label class="label">PIN</label>
        <input class="input" type="password" name="pin" autocomplete="off" />
        @error('pin') <div class="err">{{ $message }}</div> @enderror

        <div style="margin-top:12px; display:flex; gap:10px; align-items:center;">
          <input type="checkbox" name="remember" id="remember" />
          <label for="remember" style="opacity:.9;">Remember me</label>
        </div>

        <button class="btn" type="submit" style="width:100%; margin-top:16px;">
          Login
        </button>
      </form>
    </div>
  </div>
@endsection
