{{-- resources/views/partials/account_mobile_dashboard.blade.php --}}
@php
  // expects:
  // $currency, $cash, $chips, $bonus, $fmt
@endphp

<div class="mWallet" data-mdash>
  <div class="mWallet__top">
    <div class="mWallet__title">Wallet ({{ $currency }})</div>

    {{--<div class="mWallet__actions">
      <a class="mMiniAct" href="#" title="Deposit">
        <span class="mMiniAct__ico">🪙</span>
        <span class="mMiniAct__txt">Deposit</span>
      </a>
      <a class="mMiniAct" href="#" title="QR Code">
        <span class="mMiniAct__ico">🔳</span>
        <span class="mMiniAct__txt">QR Code</span>
      </a>
    </div>--}}
  </div>

  <div class="mWallet__box">
    <div class="mWallet__row">
      <span class="mWallet__label">Cash</span>
      <span class="mWallet__val">{{ $fmt($cash) }}</span>
    </div>
    <div class="mWallet__row">
      <span class="mWallet__label">Chips</span>
      <span class="mWallet__val">{{ $fmt($chips) }}</span>
    </div>
    <div class="mWallet__row">
      <span class="mWallet__label">Bonus</span>
      <span class="mWallet__val">{{ $fmt($bonus) }}</span>
    </div>
  </div>

  {{-- ✅ collapse button (only hides quick row + tiles) --}}
  <div class="mDashToggleWrap">
    <button class="mDashToggle" type="button" data-mdash-toggle aria-expanded="true">
      <span class="mDashToggle__txt">Hide shortcuts</span>
      <span class="mDashToggle__ico">▾</span>
    </button>
  </div>

  <div class="mWallet__collapse" data-mdash-collapsible>
    <div class="mQuick">
      <a class="mQuick__item" href="{{ route('history.transactions') }}" title="History">
        <span class="mQuick__ico">🕘</span>
        <span class="mQuick__txt">History</span>
      </a>
      <a class="mQuick__item" href="{{ route('history.games') }}" title="Records">
        <span class="mQuick__ico">🎮</span>
        <span class="mQuick__txt">Records</span>
      </a>
      <a class="mQuick__item" href="{{ route('withdraw.index') }}" title="Withdrawal">
        <span class="mQuick__ico">🏧</span>
        <span class="mQuick__txt">Withdrawal</span>
      </a>
      <a class="mQuick__item" href="{{ route('profile.index', ['m' => 'profile']) }}" title="Profile">
        <span class="mQuick__ico">👤</span>
        <span class="mQuick__txt">Profile</span>
      </a>
      <a class="mQuick__item" href="{{ route('referral.index') }}" title="Referral">
        <span class="mQuick__ico">👥</span>
        <span class="mQuick__txt">Referral</span>
      </a>
  </div>
</div>
