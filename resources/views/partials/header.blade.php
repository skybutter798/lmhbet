{{-- /home/lmh/app/resources/views/partials/header.blade.php --}}
@php
  $walletBalances = $walletBalances ?? [];

  $cash  = (float) ($walletBalances['main']  ?? 0);
  $chips = (float) ($walletBalances['chips'] ?? 0);
  $bonus = (float) ($walletBalances['bonus'] ?? 0);

  $fmt = function ($v) {
    return number_format((float)$v, 2, '.', ',');
  };
@endphp

<header class="topbar">
  <div class="wrap topbar__inner">

    <button class="mBurger" type="button" aria-label="Open menu" data-mdrawer-open>
      <span class="mBurger__bar"></span>
      <span class="mBurger__bar"></span>
      <span class="mBurger__bar"></span>
    </button>

    <a class="brand" href="{{ route('home') }}">
      <img class="brand__img" src="{{ asset('images/lmh_logo.png') }}" alt="LMH Logo">
      <span class="brand__name">LUCKY MONEY HOUSE</span>
    </a>

    <div class="topbar__right topbar__right--desktop">
      @guest
        <button class="link linkBtn" type="button" data-open-modal="login">Login</button>
        <button class="btn btn--primary" type="button" data-open-modal="register">Join Now</button>
      @else
        <div class="hdrIcons">
          <button class="icoBtn" type="button" aria-label="Account" data-user-menu-btn>👤</button>
          <button class="icoBtn" type="button" aria-label="Message">✉️</button>

          <div class="walletBtn" data-wallet-menu-btn data-wallet-live>
            <div class="walletBtn__top">
              <span class="walletBtn__label">Wallet ({{ auth()->user()->currency ?? 'MYR' }})</span>
              <span class="walletBtn__caret">▾</span>
            </div>
            <div class="walletBtn__amt">
              $ <span data-wallet-main>{{ $fmt($cash) }}</span>
            </div>
          </div>

          <div class="ddMenu" id="walletMenu" hidden data-wallet-menu-live>
            <a href="{{ route('wallet.index', ['tab' => 'cash']) }}" class="ddItem">
              <span>Cash</span><span>$ <span data-wallet-main>{{ $fmt($cash) }}</span></span>
            </a>
            <a href="{{ route('wallet.index', ['tab' => 'chips']) }}" class="ddItem">
              <span>Chips</span><span>$ <span data-wallet-chips>{{ $fmt($chips) }}</span></span>
            </a>
            <a href="{{ route('wallet.index', ['tab' => 'bonus']) }}" class="ddItem">
              <span>Bonus</span><span>$ <span data-wallet-bonus>{{ $fmt($bonus) }}</span></span>
            </a>
          </div>

          <div class="ddMenu" id="userMenu" hidden>
            <div class="ddHead">
              <div class="ddAvatar">{{ strtoupper(substr(auth()->user()->username ?? 'U',0,1)) }}</div>
              <div>
                <div class="ddName">{{ auth()->user()->username ?? 'User' }}</div>
                <div class="ddRole">PLAYER</div>
              </div>
            </div>

            <div class="ddSep"></div>

            <div class="ddAcc" data-accordion>
              <div class="ddAcc__item">
                <button class="ddAcc__btn" type="button" aria-expanded="false">
                  <span>Funds</span>
                  <span class="ddAcc__caret">▾</span>
                </button>
                <div class="ddAcc__panel" hidden>
                  <a class="ddItem ddItem--sub" href="{{ route('wallet.index') }}">Wallet</a>
                  <a class="ddItem ddItem--sub" href="{{ route('deposit.index') }}">Deposit</a>
                  <a class="ddItem ddItem--sub" href="{{ route('withdraw.index') }}">Withdrawal</a>
                  <a class="ddItem ddItem--sub" href="{{ route('transfer.index') }}">Transfer</a>
                </div>
              </div>

              <div class="ddAcc__item">
                <button class="ddAcc__btn" type="button" aria-expanded="false">
                  <span>Rewards</span>
                  <span class="ddAcc__caret">▾</span>
                </button>
                <div class="ddAcc__panel" hidden>
                  <a class="ddItem ddItem--sub" href="{{ route('referral.index') }}">Referral</a>
                </div>
              </div>

              <div class="ddAcc__item">
                <button class="ddAcc__btn" type="button" aria-expanded="false">
                  <span>Profile</span>
                  <span class="ddAcc__caret">▾</span>
                </button>
                <div class="ddAcc__panel" hidden>
                  <a class="ddItem ddItem--sub" href="{{ route('profile.index') }}">My Profile</a>
                  <a class="ddItem ddItem--sub" href="#">Bank Details</a>
                  <a class="ddItem ddItem--sub" href="#">Message</a>
                  <a class="ddItem ddItem--sub" href="#">Change Password</a>
                  <a class="ddItem ddItem--sub" href="#">Change PIN</a>
                </div>
              </div>

              <div class="ddAcc__item">
                <button class="ddAcc__btn" type="button" aria-expanded="false">
                  <span>History</span>
                  <span class="ddAcc__caret">▾</span>
                </button>
                <div class="ddAcc__panel" hidden>
                  <a class="ddItem ddItem--sub" href="{{ route('history.transactions') }}">Transaction History</a>
                  <a class="ddItem ddItem--sub" href="{{ route('history.games') }}">Game History</a>
                  <a class="ddItem ddItem--sub" href="{{ route('profile.index') }}">Bonus History</a>
                </div>
              </div>
            </div>

            <div class="ddSep ddSep--strong"></div>

            <form action="{{ route('logout') }}" method="post">
              @csrf
              <button class="ddItem ddBtn" type="submit">Logout</button>
            </form>
          </div>
        </div>
      @endguest
    </div>
  </div>
</header>

<div class="mDrawer" id="mDrawer" hidden aria-hidden="true">
  <div class="mDrawer__backdrop" data-mdrawer-close></div>

  <aside class="mDrawer__panel" role="dialog" aria-label="Mobile menu">
    <div class="mDrawer__top">
      @auth
        <button class="mWallet" type="button" data-mwallet-btn data-wallet-live>
          <div class="mWallet__title">
            <span>Wallet ({{ auth()->user()->currency ?? 'MYR' }})</span>
            <span class="mWallet__caret">▾</span>
          </div>
          <div class="mWallet__amt">
            $ <span data-wallet-main>{{ $fmt($cash) }}</span>
          </div>
        </button>

        <div class="mWalletMenu" id="mWalletMenu" hidden>
          <a class="mWalletMenu__item" href="{{ route('wallet.index', ['tab' => 'cash']) }}">
            <span>Cash</span><span>$ <span data-wallet-main>{{ $fmt($cash) }}</span></span>
          </a>
          <a class="mWalletMenu__item" href="{{ route('wallet.index', ['tab' => 'chips']) }}">
            <span>Chips</span><span>$ <span data-wallet-chips>{{ $fmt($chips) }}</span></span>
          </a>
          <a class="mWalletMenu__item" href="{{ route('wallet.index', ['tab' => 'bonus']) }}">
            <span>Bonus</span><span>$ <span data-wallet-bonus>{{ $fmt($bonus) }}</span></span>
          </a>
        </div>
      @else
        <div class="mDrawer__guestTitle">Menu</div>
      @endauth

      <button class="mDrawer__close" type="button" aria-label="Close menu" data-mdrawer-close>✕</button>
    </div>

    <div class="mGrid">
      <a class="mGrid__item is-active" href="{{ route('home') }}">
        <span class="mGrid__ico">🏠</span><span class="mGrid__lbl">Home</span>
      </a>
      <a class="mGrid__item" href="{{ route('games.index') }}">
        <span class="mGrid__ico">⚙️</span><span class="mGrid__lbl">Promotions</span>
      </a>
      <a class="mGrid__item" href="{{ route('games.index', ['cat' => 'slots']) }}">
        <span class="mGrid__ico">🎰</span><span class="mGrid__lbl">Slots</span>
      </a>
      <a class="mGrid__item" href="{{ route('games.index', ['cat' => 'sports']) }}">
        <span class="mGrid__ico">⚽</span><span class="mGrid__lbl">Sports</span>
      </a>
      <a class="mGrid__item" href="{{ route('games.index', ['cat' => 'lottery']) }}">
        <span class="mGrid__ico">7️⃣</span><span class="mGrid__lbl">Lottery</span>
      </a>
      <a class="mGrid__item" href="{{ route('games.index', ['cat' => 'casino']) }}">
        <span class="mGrid__ico">🃏</span><span class="mGrid__lbl">Casino</span>
      </a>
    </div>

    @auth
      <div class="mGrid mGrid--dense">
        <a class="mGrid__item" href="{{ route('wallet.index') }}"><span class="mGrid__ico">💳</span><span class="mGrid__lbl">Wallet</span></a>
        <a class="mGrid__item" href="{{ route('deposit.index') }}"><span class="mGrid__ico">🪙</span><span class="mGrid__lbl">Deposit</span></a>
        <a class="mGrid__item" href="{{ route('referral.index') }}"><span class="mGrid__ico">👥</span><span class="mGrid__lbl">Referral</span></a>

        <a class="mGrid__item" href="{{ route('transfer.index') }}"><span class="mGrid__ico">↔️</span><span class="mGrid__lbl">Transfer</span></a>
        <a class="mGrid__item" href="{{ route('withdraw.index') }}"><span class="mGrid__ico">🏧</span><span class="mGrid__lbl">Withdrawal</span></a>
        <a class="mGrid__item" href="#"><span class="mGrid__ico">✉️</span><span class="mGrid__lbl">Message</span></a>

        <a class="mGrid__item" href="{{ route('profile.index') }}"><span class="mGrid__ico">👤</span><span class="mGrid__lbl">Profile</span></a>
        <a class="mGrid__item" href="{{ route('history.games') }}"><span class="mGrid__ico">🎮</span><span class="mGrid__lbl">Game</span></a>
        <a class="mGrid__item" href="{{ route('history.transactions') }}"><span class="mGrid__ico">📜</span><span class="mGrid__lbl">History</span></a>
      </div>
    @endauth

    <div class="mDrawer__bottom">
      <div class="mDrawer__miniLinks">
        <a class="mMiniLink" href="#">About Us</a>
        <a class="mMiniLink" href="#">Contact Us</a>
        <a class="mMiniLink" href="#">T&amp;C</a>
      </div>

      @guest
        <button class="mDrawer__cta" type="button" data-open-modal="login" data-mdrawer-close>
          Login
        </button>
      @else
        <form action="{{ route('logout') }}" method="post">
          @csrf
          <button class="mDrawer__cta mDrawer__cta--ghost" type="submit">
            Logout
          </button>
        </form>
      @endguest
    </div>
  </aside>
</div>