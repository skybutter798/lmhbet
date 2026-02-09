{{-- /home/lmh/app/resources/views/referral/index.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
  @endphp

  <main class="accPage">

    {{-- =========================
        MOBILE (Referral page)
        ========================= --}}
    <section class="accMobile">
      <div class="wrap">

        {{-- Mobile header + wallet --}}
        @include('partials.account_mobile_dashboard', [
          'currency' => $currency,
          'cash' => $cash,
          'chips' => $chips,
          'bonus' => $bonus,
          'fmt' => $fmt,
        ])


          {{-- Referral content (no icon tiles here) --}}
          <div class="mBlock">
            <div class="mRef">
              <div class="mRef__item">
                <div class="mRef__label">Referral Code</div>
                <div class="mRef__row">
                  <input class="mRef__input" type="text" value="{{ $referralCode }}" readonly>
                  <button class="mRef__copy" type="button" data-copy-value="{{ $referralCode }}">Copy</button>
                </div>
              </div>

              <div class="mRef__item">
                <div class="mRef__label">Referral Url</div>
                <div class="mRef__row">
                  <input class="mRef__input" type="text" value="{{ $referralUrl }}" readonly>
                  <button class="mRef__copy" type="button" data-copy-value="{{ $referralUrl }}">Copy</button>
                </div>
              </div>

              <div class="mRef__item">
                <div class="mRef__label">Referral Count</div>
                <div class="mRef__row">
                  <input class="mRef__input" type="text" value="Member Invited" readonly>
                  <div class="mRef__count">
                    <span class="mRef__num">{{ $referralCount }}</span>
                    <span class="mRef__muted">members</span>
                  </div>
                </div>
              </div>

              <div class="mRef__item">
                <div class="mRef__label">Referral QR</div>
                <div class="mRef__qr">
                  <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=190x190&data={{ urlencode($referralUrl) }}"
                    alt="Referral QR"
                    loading="lazy"
                  >
                </div>
              </div>
            </div>
          </div>

          {{-- Terms --}}
          <div class="refTerms refTerms--m">
            <button class="refTerms__head" type="button" data-ref-terms-toggle>
              <span class="refTerms__left">Terms &amp; Conditions</span>
              <span class="refTerms__chev">▾</span>
            </button>

            <div class="refTerms__body" data-ref-terms-body>
              <ol class="refOl">
                <li>Commissions are calculated based on the daily valid turnover of your downline members and will be credited to your rebate wallet according to the commission rates of each game provider.</li>
                <li>The following bet types will not be counted towards commission calculations:
                  <ul>
                    <li>Cancelled bets, draw results, invalid bets</li>
                    <li>Hedging behavior (e.g. placing opposite bets on the same event)</li>
                  </ul>
                </li>
                <li>All bets placed in Blackjack will be excluded from commission calculations.</li>
                <li>This promotion cannot be combined with other ongoing bonuses or promotions.</li>
                <li>Special high commission campaigns for specific games will be launched from time to time.</li>
                <li>The platform reserves the right to modify, suspend, cancel, or terminate this promotion and/or amend its terms and conditions at any time without prior notice.</li>
              </ol>

              <div class="refMore">
                <div class="refMore__title">Earn Commissions Easily</div>
                <ul class="refUl">
                  <li>By sharing your referral QR code or link, you automatically become the recommender of the referred user.</li>
                  <li>In addition to your own betting rebates, you also earn commissions based on your downline members' valid bets.</li>
                  <li>The more players you invite, the more commissions you can earn — enjoy real passive income while playing.</li>
                </ul>
              </div>

              <button class="refTerms__toggle" type="button" data-ref-terms-more>Show Less</button>
            </div>
          </div>

        </div>
      </div>
    </section>

    {{-- =========================
        DESKTOP (existing)
        ========================= --}}
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'reward', 'activeSub' => 'referral', 'referralCode' => $referralCode])

      <section class="accMain">

        {{-- TOP WALLET SUMMARY --}}
        <div class="profileTop">
          <div class="profileTop__title">Wallet ({{ $currency }})</div>

          <div class="profileWalletBox">
            <div class="profileWalletRow">
              <div class="profileWalletLabel">Cash</div>
              <div class="profileWalletVal">{{ $fmt($cash) }}</div>
            </div>
            <div class="profileWalletRow">
              <div class="profileWalletLabel">Chips</div>
              <div class="profileWalletVal">{{ $fmt($chips) }}</div>
            </div>
            <div class="profileWalletRow">
              <div class="profileWalletLabel">Bonus</div>
              <div class="profileWalletVal">{{ $fmt($bonus) }}</div>
            </div>
          </div>
        </div>

        {{-- REFERRAL CARD --}}
        <div class="refGrid">
          <div class="refCard">
            <div class="refCard__grid">
              <div>
                <div class="refLabel">Referral Code</div>
                <div class="refInputRow">
                  <input class="refInput" type="text" value="{{ $referralCode }}" readonly>
                  <button class="refCopy" type="button" data-copy-value="{{ $referralCode }}">⧉</button>
                </div>
              </div>

              <div>
                <div class="refLabel">Referral Url</div>
                <div class="refInputRow">
                  <input class="refInput" type="text" value="{{ $referralUrl }}" readonly>
                  <button class="refCopy" type="button" data-copy-value="{{ $referralUrl }}">⧉</button>
                </div>
              </div>

              <div>
                <div class="refLabel">Referral Count</div>
                <div class="refInputRow">
                  <input class="refInput" type="text" value="Member Invited" readonly>
                  <div class="refCount">
                    <span class="refCount__num">{{ $referralCount }}</span>
                    <span class="refCount__ico">👥</span>
                  </div>
                </div>
              </div>

              <div>
                <div class="refLabel">Referral QR</div>
                <div class="refQr">
                  <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=190x190&data={{ urlencode($referralUrl) }}"
                    alt="Referral QR"
                    loading="lazy"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- TERMS --}}
        <div class="refTerms">
          <button class="refTerms__head" type="button" data-ref-terms-toggle>
            <span class="refTerms__left"><span class="refWarn">⚠</span> Terms &amp; Conditions</span>
            <span class="refTerms__chev">▾</span>
          </button>

          <div class="refTerms__body" data-ref-terms-body>
            <ol class="refOl">
              <li>Commissions are calculated based on the daily valid turnover of your downline members and will be credited to your rebate wallet according to the commission rates of each game provider.</li>
              <li>The following bet types will not be counted towards commission calculations:
                <ul>
                  <li>Cancelled bets, draw results, invalid bets</li>
                  <li>Hedging behavior (e.g. placing opposite bets on the same event)</li>
                </ul>
              </li>
              <li>All bets placed in Blackjack will be excluded from commission calculations.</li>
              <li>This promotion cannot be combined with other ongoing bonuses or promotions.</li>
              <li>Special high commission campaigns for specific games will be launched from time to time.</li>
              <li>The platform reserves the right to modify, suspend, cancel, or terminate this promotion and/or amend its terms and conditions at any time without prior notice.</li>
            </ol>

            <div class="refMore">
              <div class="refMore__title">Earn Commissions Easily</div>
              <ul class="refUl">
                <li>By sharing your referral QR code or link, you automatically become the recommender of the referred user.</li>
                <li>In addition to your own betting rebates, you also earn commissions based on your downline members' valid bets.</li>
                <li>The more players you invite, the more commissions you can earn — enjoy real passive income while playing.</li>
              </ul>
            </div>

            <button class="refTerms__toggle" type="button" data-ref-terms-more>Show Less</button>
          </div>
        </div>

      </section>
    </div>
  </main>

  <script>
    (function () {
      function copyText(text) {
        if (!text) return;

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(text).catch(function(){});
          return;
        }
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        ta.style.top = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
      }

      document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy-value]');
        if (btn) {
          e.preventDefault();
          copyText(btn.getAttribute('data-copy-value'));

          var original = btn.textContent;
          btn.textContent = '✓';
          setTimeout(function () { btn.textContent = original; }, 800);
          return;
        }

        if (e.target.closest('[data-ref-terms-toggle]') || e.target.closest('[data-ref-terms-more]')) {
          e.preventDefault();
          var body = document.querySelector('[data-ref-terms-body]');
          if (!body) return;
          body.classList.toggle('is-open');

          var moreBtn = document.querySelector('[data-ref-terms-more]');
          if (moreBtn) moreBtn.textContent = body.classList.contains('is-open') ? 'Show Less' : 'More';
          return;
        }
      });
    })();
  </script>
@endsection
