@extends('layouts.app')

@section('body')
  @include('partials.header')

  <main>
    {{-- HERO / SLIDER (image banners only) --}}
    @php
      $slides = [
        'images/sliders/01.webp',
        'images/sliders/02.webp',
      ];
    @endphp
    
    <section class="hero">
      <div class="hero__slides" data-slider>
    
        @foreach($slides as $i => $path)
          <article class="hero__slide {{ $i === 0 ? 'is-active' : '' }}">
            <div class="wrap">
              <a class="heroBanner" href="#" aria-label="Banner {{ $i + 1 }}">
                <img
                  src="{{ asset($path) }}"
                  alt="LMH banner {{ $i + 1 }}"
                  loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                  fetchpriority="{{ $i === 0 ? 'high' : 'auto' }}"
                />
              </a>
            </div>
          </article>
        @endforeach
    
        <button class="hero__nav hero__nav--prev" type="button" data-prev aria-label="Previous">‹</button>
        <button class="hero__nav hero__nav--next" type="button" data-next aria-label="Next">›</button>
    
        <div class="hero__dots" data-dots>
          @foreach($slides as $i => $path)
            <button
              class="dotbtn {{ $i === 0 ? 'is-active' : '' }}"
              type="button"
              data-dot="{{ $i }}"
              aria-label="Slide {{ $i + 1 }}"
            ></button>
          @endforeach
        </div>
    
      </div>
    </section>
    
    {{-- GUEST CTA (between slider and hot games) --}}
    @guest
      <section class="section section--tight">
        <div class="wrap">
          <div class="guestCta">
            <div class="guestCta__left">
              <div class="guestCta__kicker">Welcome</div>
              <div class="guestCta__title">Login to view your wallet & start playing</div>
              <div class="guestCta__sub">Sign in or create an account in seconds.</div>
            </div>
    
            <div class="guestCta__right">
              <button class="btn btn--ghost btn--lg" type="button" data-open-modal="login">
                Login
              </button>
              <button class="btn btn--primary btn--lg" type="button" data-open-modal="register">
                Register
              </button>
            </div>
          </div>
        </div>
      </section>
    @endguest


    {{-- PROMO STRIP (guest) / WALLET STRIP (logged-in) --}}
    @guest
      {{--<section class="section section--tight">
        <div class="wrap">
          <div class="promoStrip">
            @for($i=1;$i<=4;$i++)
              <a href="#" class="promoCard">
                <div class="ph ph--promo" aria-hidden="true">
                  <div class="ph__label">PROMO {{$i}}</div>
                </div>
                <div class="promoCard__meta">
                  <div class="promoCard__title">Top-up Bonus</div>
                  <div class="promoCard__sub">Up to 88%</div>
                </div>
              </a>
            @endfor
            <a href="#" class="promoStrip__more">More</a>
          </div>
        </div>
      </section>--}}
    @else
      @php
        $cash  = $walletBalances['main']  ?? 0;
        $chips = $walletBalances['chips'] ?? 0;
        $bonus = $walletBalances['bonus'] ?? 0;
    
        $fmt = function ($v) {
          return number_format((float)$v, 2, '.', ',');
        };
    
        $total = (float)$cash + (float)$chips + (float)$bonus;
      @endphp
    
      <section class="section section--tight">
        <div class="wrap">
          <div class="walletStrip walletStrip--acc" data-wallet-acc>
            {{-- Clickable header --}}
            <button
              class="walletStrip__head"
              type="button"
              data-wallet-acc-btn
              aria-expanded="true"
              aria-controls="walletAccBody"
            >
              <div class="walletStrip__headLeft">
                <div class="walletStrip__kicker">Wallet ({{ auth()->user()->currency ?? 'MYR' }})</div>
                <div class="walletStrip__total">$ {{ $fmt($total) }}</div>
              </div>
    
              <span class="walletStrip__caret" aria-hidden="true">⌄</span>
            </button>
    
            {{-- Collapsible body --}}
            <div class="walletStrip__body" id="walletAccBody" data-wallet-acc-body>
              <div class="walletStrip__mini">
                <div class="wMini">
                  <div class="wMini__label">Cash</div>
                  <div class="wMini__value">$ {{ $fmt($cash) }}</div>
                </div>
                <div class="wMini">
                  <div class="wMini__label">Chips</div>
                  <div class="wMini__value">$ {{ $fmt($chips) }}</div>
                </div>
                <div class="wMini">
                  <div class="wMini__label">Bonus</div>
                  <div class="wMini__value">$ {{ $fmt($bonus) }}</div>
                </div>
              </div>
    
              <div class="walletStrip__right">
                <a class="btn btn--primary" href="{{ route('deposit.index') }}">Deposit</a>
                <a class="btn btn--ghost" href="{{ route('withdraw.index') }}">Withdraw</a>
                <a class="btn btn--ghost" href="{{ route('transfer.index') }}">Transfer</a>
                <a class="btn btn--ghost" href="{{ route('wallet.index') }}">Wallet</a>
              </div>
            </div>
          </div>
        </div>
      </section>
    @endguest



    {{-- HOT GAMES ROW --}}
    <section class="section">
      <div class="wrap">
        <div class="sectionHead">
          <h2>Hot Games</h2>
        </div>
    
        <div class="rowScroll">
          @foreach($hotGames as $g)
            <a class="gameCard" href="#" title="{{ $g->name }}" data-launch-game data-game-id="{{ $g->id }}" >
            <div class="ph ph--game {{ $g->primaryImage ? 'has-img' : '' }}" aria-hidden="true" data-ph="{{ $g->code }}">
              @if($g->primaryImage)
                <img
                  class="ph__img ph__img--cover"
                  src="{{ asset(ltrim($g->primaryImage->path, '/')) }}"
                  alt="{{ $g->name }}"
                  loading="lazy"
                >
              @endif
            
              <div class="ph__label"><span>{{ $g->name }}</span></div>
            </div>

            
            <div class="gameCard__name">{{ $g->provider?->name }}</div>
            </a>
          @endforeach
        </div>
    
      </div>
    </section>


    {{-- CATEGORY TABS (product_group_name) + PROVIDER GRID --}}
    <section class="section section--panel">
      <div class="wrap">
        <div class="tabs" data-tabs>
          <button class="tab is-active" type="button" data-filter="all">All</button>

          @foreach($productGroups as $pg)
            <button class="tab" type="button" data-filter="{{ $pg['key'] }}">
              {{ $pg['label'] }}
            </button>
          @endforeach
        </div>

        <div class="grid">
          @foreach($providers as $p)
            @php
              $keys = $providerGroups[$p->id]['keys'] ?? ['other'];
              $dataCat = implode(' ', $keys); // IMPORTANT: space-separated list
            @endphp
        
            <a href="{{ url('/games?provider='.$p->code) }}" class="tile" data-cat="{{ $dataCat }}">
                <div class="ph ph--tile {{ $p->primaryImage ? 'has-img' : '' }}" aria-hidden="true" data-ph="{{ $p->code }}">
                  @if($p->primaryImage)
                    <img
                      class="ph__img ph__img--contain"
                      src="{{ asset(ltrim($p->primaryImage->path, '/')) }}"
                      alt="{{ $p->name }}"
                      loading="lazy"
                    >
                  @endif
                
                  <div class="ph__label"><span>{{ $p->name }}</span></div>
                </div>

            </a>
          @endforeach
        </div>

      </div>
    </section>

    {{-- WELCOME + STEPS --}}
    <section class="section">
      <div class="wrap center">
        <div class="kicker">Welcome to LMH - Trusted Online Casino</div>
        <h2 class="headline">Start Playing and Earning</h2>

        <div class="steps">
          <div class="step">
            <div class="step__icon">👤</div>
            <div class="step__title">Create account</div>
            <div class="step__desc">Click Join Now. Fill in your login info.</div>
          </div>
          <div class="step">
            <div class="step__icon">💳</div>
            <div class="step__title">Make a deposit</div>
            <div class="step__desc">Use online banking / crypto transfer.</div>
          </div>
          <div class="step">
            <div class="step__icon">🎮</div>
            <div class="step__title">Start winning</div>
            <div class="step__desc">Play your favourite games instantly.</div>
          </div>
          <div class="step">
            <div class="step__icon">🎁</div>
            <div class="step__title">Get reward</div>
            <div class="step__desc">Claim bonuses and promotions daily.</div>
          </div>
        </div>
      </div>
    </section>
  </main>
  @include('partials.footer')
@endsection
