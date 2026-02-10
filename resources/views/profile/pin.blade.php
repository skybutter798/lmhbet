@extends('layouts.app')

@section('body')
  @include('partials.header')

  <main class="accPage">
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'profile', 'activeSub' => 'change_pin'])

      <section class="accMain">
        <h1 style="margin: 0 0 12px;">Change PIN</h1>

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
            <form method="post" action="{{ route('profile.pin.update') }}">
              @csrf

              @php $hasPin = !empty(auth()->user()->pin); @endphp

              @if ($hasPin)
                <label class="pLabel">Current PIN <span class="req">*</span></label>
                <input class="pInput" type="password" name="current_pin" inputmode="numeric" autocomplete="off" required>
              @endif

              <label class="pLabel">New PIN (4–6 digits) <span class="req">*</span></label>
              <input class="pInput" type="password" name="pin" inputmode="numeric" autocomplete="off" required>

              <label class="pLabel">Confirm New PIN <span class="req">*</span></label>
              <input class="pInput" type="password" name="pin_confirmation" inputmode="numeric" autocomplete="off" required>

              <button class="pSubmit" type="submit" style="margin-top: 12px;">Update PIN</button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </main>
@endsection