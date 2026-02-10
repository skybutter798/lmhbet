@extends('layouts.app')

@section('body')
  @include('partials.header')

  <main class="accPage">
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'profile', 'activeSub' => 'change_password'])

      <section class="accMain">
        <h1 style="margin: 0 0 12px;">Change Password</h1>

        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 18px;">
              @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="profileCard">
          <div class="profileSection">
            <form method="post" action="{{ route('profile.password.update') }}">
              @csrf

              <label class="pLabel">Current Password <span class="req">*</span></label>
              <input class="pInput" type="password" name="current_password" required>

              <label class="pLabel">New Password <span class="req">*</span></label>
              <input class="pInput" type="password" name="password" required>

              <label class="pLabel">Confirm New Password <span class="req">*</span></label>
              <input class="pInput" type="password" name="password_confirmation" required>

              <button class="pSubmit" type="submit" style="margin-top: 12px;">Update Password</button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </main>
@endsection