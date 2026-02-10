<aside class="accSide">
  @php
    $active = $active ?? '';
    $activeSub = $activeSub ?? '';
  @endphp

  <div class="accSide__panel">
    <div class="accNav">

      {{-- FUNDS --}}
      <div class="accNav__group">
        <div class="accNav__title">
          <span class="accNav__icon">💼</span>
          <span>Funds</span>
          <span class="accNav__chev">▾</span>
        </div>

        <a class="accNav__link {{ request()->routeIs('wallet.*') ? 'is-active' : '' }}"
           href="{{ route('wallet.index') }}">Wallet</a>
        
        <a class="accNav__link {{ request()->routeIs('deposit.*') ? 'is-active' : '' }}"
           href="{{ route('deposit.index') }}">Deposit</a>
        
        <a class="accNav__link {{ request()->routeIs('withdraw.*') ? 'is-active' : '' }}"
           href="{{ route('withdraw.index') }}">Withdrawal</a>
        
        <a class="accNav__link {{ request()->routeIs('transfer.*') ? 'is-active' : '' }}"
           href="{{ route('transfer.index') }}">Transfer</a>

      </div>

      {{-- REWARD --}}
      <div class="accNav__group">
        <div class="accNav__title">
          <span class="accNav__icon">🎁</span>
          <span>Reward</span>
          <span class="accNav__chev">›</span>
        </div>

        <a class="accNav__link {{ $active==='reward' && $activeSub==='referral' ? 'is-active' : '' }}"
           href="{{ route('referral.index') }}">
          Referral
        </a>
      </div>

      {{-- PROFILE --}}
      <div class="accNav__group">
        <div class="accNav__title">
          <span class="accNav__icon">👤</span>
          <span>Profile</span>
          <span class="accNav__chev">›</span>
        </div>

        <a class="accNav__link {{ $active==='profile' && $activeSub==='my_profile' ? 'is-active' : '' }}"
           href="{{ route('profile.index') }}">My Profile</a>
        
        <a class="accNav__link {{ $active==='profile' && $activeSub==='bank_details' ? 'is-active' : '' }}"
           href="{{ route('profile.bank') }}">Bank Details</a>
        
        <a class="accNav__link {{ request()->routeIs('support.*') ? 'is-active' : '' }}"
            href="{{ route('support.index') }}">Message</a>
        
        <a class="accNav__link {{ $active==='profile' && $activeSub==='change_password' ? 'is-active' : '' }}"
           href="{{ route('profile.password.form') }}">Change Password</a>
        
        <a class="accNav__link {{ $active==='profile' && $activeSub==='change_pin' ? 'is-active' : '' }}"
           href="{{ route('profile.pin.form') }}">Change PIN</a>
      </div>

      {{-- HISTORY (dropdown) --}}
      <details class="accNav__group accNav__group--drop" {{ $active==='history' ? 'open' : '' }}>
        <summary class="accNav__title">
          <span class="accNav__icon">🕘</span>
          <span>History</span>
          <span class="accNav__chev">▾</span>
        </summary>

        <a class="accNav__link {{ $active==='history' && $activeSub==='transactions' ? 'is-active' : '' }}"
           href="{{ route('history.transactions') }}">
          Transaction History
        </a>

        <a class="accNav__link {{ $active==='history' && $activeSub==='games' ? 'is-active' : '' }}"
           href="{{ route('history.games') }}">
          Game History
        </a>
      </details>

    </div>
  </div>

  <div class="accSide__invite">
    <div class="inviteCard">
      <div class="inviteCard__title">Invite Your Friends and Earn Rewards!</div>
      <div class="inviteCard__text">
        Share your referral code and enjoy exclusive bonuses when your friends sign up and play!
      </div>

      <div class="inviteCode">
        <input class="inviteCode__input" type="text" value="{{ $referralCode ?? (auth()->user()->referral_code ?? '') }}" readonly>
        <button class="inviteCode__copy" type="button" data-copy-ref>Copy</button>
      </div>
    </div>
  </div>
</aside>
