<nav class="mNav" aria-label="Mobile navigation">
  <div class="mNav__wrap">
    <div class="mNav__bg" aria-hidden="true">
      <svg viewBox="0 0 768 64" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M256 0H0V64H768V0H512
          C472 0 456 30 446 34
          C436 38 414 54 384 54
          C354 54 332 46 322 35
          C312 24 296 4 256 0Z"/>
      </svg>
    </div>

    <div class="mNav__bar">
      <a href="{{ url('/promotions') }}"
         class="mNav__item {{ request()->is('promotions*') ? 'is-active' : '' }}">
        <span class="mNav__ico" aria-hidden="true">
          <!-- Gift outline -->
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M20 12v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8" />
            <path d="M22 7H2v5h20V7Z" />
            <path d="M12 22V7" />
            <path d="M12 7H7.5a2.5 2.5 0 1 1 0-5C10 2 12 7 12 7Z" />
            <path d="M12 7h4.5a2.5 2.5 0 1 0 0-5C14 2 12 7 12 7Z" />
          </svg>
        </span>
        <span class="mNav__lbl">Promotions</span>
      </a>

      <a href="{{ url('/referral') }}"
         class="mNav__item {{ request()->is('referral*') ? 'is-active' : '' }}">
        <span class="mNav__ico" aria-hidden="true">
          <!-- Users outline -->
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </span>
        <span class="mNav__lbl">Referral</span>
      </a>

      <!-- Center Home Button -->
      <a href="{{ route('home') }}"
         class="mNav__home {{ request()->is('/') ? 'is-active' : '' }}"
         aria-label="Home">
        <img class="mNav__homeLogo"
             src="https://app.lmh.bet/images/lmh_logo.png"
             alt="LMH" />
      </a>

      <a href="{{ url('/deposit') }}"
         class="mNav__item {{ request()->is('deposit*') ? 'is-active' : '' }}">
        <span class="mNav__ico" aria-hidden="true">
          <!-- Wallet outline -->
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M21 12v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2" />
            <path d="M21 10h-6a2 2 0 0 0 0 4h6v-4Z" />
            <path d="M16 12h.01" />
          </svg>
        </span>
        <span class="mNav__lbl">Deposit</span>
      </a>

      <a href="{{ url('/profile') }}"
         class="mNav__item {{ request()->is('profile*') || request()->is('profile*') ? 'is-active' : '' }}">
        <span class="mNav__ico" aria-hidden="true">
          <!-- User outline -->
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M20 21a8 8 0 0 0-16 0" />
            <path d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
          </svg>
        </span>
        <span class="mNav__lbl">Account</span>
      </a>
    </div>
  </div>
</nav>
