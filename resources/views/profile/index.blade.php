{{-- /home/lmh/app/resources/views/profile/index.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
    $u = auth()->user();

    $email = $u->email ?: null;
    $phone = $u->phone ? (($u->phone_country ?: '') . ' ' . $u->phone) : null;

    // mobile view switch: /my-account?m=profile
    $mView = request('m');
  @endphp

  <main class="accPage">

    {{-- =========================
        MOBILE
        - default: dashboard
        - ?m=profile : show profile details, hide all icons/tiles
        ========================= --}}
    <section class="accMobile">
      <div class="wrap">

        @if($mView === 'profile')
          {{-- MOBILE PROFILE DETAILS --}}
          <div class="mProfile">
            <div class="mProfile__head">
              <a class="mBack" href="{{ route('profile.index') }}">← Back</a>
              <div class="mProfile__title">My Profile</div>
            </div>

            {{-- TABS --}}
            <div class="profileTabs">
              <a class="profileTab {{ !$isKycTab ? 'is-active' : '' }}"
                 href="{{ route('profile.index', array_filter(['m' => 'profile'])) }}">
                My Profile
              </a>

              <a class="profileTab {{ $isKycTab ? 'is-active' : '' }}"
                 href="{{ route('profile.index', array_filter(['m' => 'profile', 'kyc' => true])) }}">
                Identity Verification <span class="warn">⚠</span>
              </a>
            </div>

            @if($isKycTab)
              @include('partials.account_kyc_section', [
                'latestKyc' => $latestKyc,
                'banks' => $banks,
              ])
            @else
              {{-- CARD --}}
              <div class="profileCard">
                <div class="profileSection">
                  <div class="profileSection__title">Personal Information</div>

                  <div class="profileGrid">
                    <div class="profileRow">
                      <div class="profileKey">Username</div>
                      <div class="profileVal">{{ $u->username }}</div>
                    </div>
                    <div class="profileRow">
                      <div class="profileKey">Currency</div>
                      <div class="profileVal">{{ $currencyName }}</div>
                    </div>
                    <div class="profileRow">
                      <div class="profileKey">Country</div>
                      <div class="profileVal">{{ $countryName }}</div>
                    </div>
                    <div class="profileRow">
                      <div class="profileKey">VIP Group</div>
                      <div class="profileVal">{{ $vipGroup }}</div>
                    </div>
                    <div class="profileRow">
                      <div class="profileKey">Referrer</div>
                      <div class="profileVal">{{ $referrerMasked }}</div>
                    </div>
                  </div>
                </div>

                <div class="profileDivider"></div>

                <div class="profileSection">
                  <div class="profileSection__title">Contact Information</div>

                  <div class="profileGrid">
                    <div class="profileRow">
                      <div class="profileKey">Email Address</div>
                      <div class="profileVal">
                        @if($email)
                          {{ $email }}
                        @else
                          <a href="#" class="profileAction" data-open-prof-modal="email">+ Add</a>
                        @endif
                      </div>
                    </div>

                    <div class="profileRow">
                      <div class="profileKey">Contact</div>
                      <div class="profileVal">
                        @if($phone)
                          {{ $phone }}
                          <a href="#" class="profileAction profileAction--edit" data-open-prof-modal="phone">✎ Edit</a>
                        @else
                          <a href="#" class="profileAction" data-open-prof-modal="phone">+ Add</a>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endif
          </div>

        @else
          {{-- MOBILE DASHBOARD --}}
          @include('partials.account_mobile_dashboard', [
            'currency' => $currency,
            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,
            'fmt' => $fmt,
          ])

          <div class="mTiles">
            <a class="mTile" href="{{ route('profile.index', ['m' => 'profile']) }}">
              <span class="mTile__ico">👤</span>
              <span class="mTile__txt">Profile</span>
            </a>

            <a class="mTile" href="{{ route('wallet.index') }}">
              <span class="mTile__ico">💼</span>
              <span class="mTile__txt">Wallet</span>
            </a>

            <a class="mTile" href="{{ route('referral.index') }}">
              <span class="mTile__ico">👥</span>
              <span class="mTile__txt">Referral</span>
            </a>

            <a class="mTile" href="#">
              <span class="mTile__ico">✉️</span>
              <span class="mTile__txt">Message</span>
            </a>

            <a class="mTile" href="#">
              <span class="mTile__ico">🏦</span>
              <span class="mTile__txt">Bank</span>
            </a>

            <a class="mTile" href="#">
              <span class="mTile__ico">🔐</span>
              <span class="mTile__txt">Change PIN</span>
            </a>

            <a class="mTile" href="#">
              <span class="mTile__ico">🔑</span>
              <span class="mTile__txt">Change Password</span>
            </a>

            <a class="mTile" href="{{ route('history.transactions') }}">
              <span class="mTile__ico">🕘</span>
              <span class="mTile__txt">History</span>
            </a>

            <a class="mTile" href="{{ route('history.games') }}">
              <span class="mTile__ico">🎮</span>
              <span class="mTile__txt">Records</span>
            </a>
          </div>
        @endif

      </div>
    </section>

    {{-- =========================
        DESKTOP (SIDEBAR LEFT)
        ========================= --}}
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'profile', 'activeSub' => 'my_profile'])

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

        {{-- TABS --}}
        <div class="profileTabs">
          <a class="profileTab {{ !$isKycTab ? 'is-active' : '' }}"
             href="{{ route('profile.index') }}">
            My Profile
          </a>

          <a class="profileTab {{ $isKycTab ? 'is-active' : '' }}"
             href="{{ route('profile.index', ['kyc' => true]) }}">
            Identity Verification <span class="warn">⚠</span>
          </a>
        </div>

        @if($isKycTab)
          @include('partials.account_kyc_section', [
            'latestKyc' => $latestKyc,
            'banks' => $banks,
          ])
        @else
          {{-- CARD --}}
          <div class="profileCard">
            <div class="profileSection">
              <div class="profileSection__title">Personal Information</div>

              <div class="profileGrid">
                <div class="profileRow">
                  <div class="profileKey">Username</div>
                  <div class="profileVal">{{ $u->username }}</div>
                </div>
                <div class="profileRow">
                  <div class="profileKey">Currency</div>
                  <div class="profileVal">{{ $currencyName }}</div>
                </div>
                <div class="profileRow">
                  <div class="profileKey">Country</div>
                  <div class="profileVal">{{ $countryName }}</div>
                </div>
                <div class="profileRow">
                  <div class="profileKey">VIP Group</div>
                  <div class="profileVal">{{ $vipGroup }}</div>
                </div>
                <div class="profileRow">
                  <div class="profileKey">Referrer</div>
                  <div class="profileVal">{{ $referrerMasked }}</div>
                </div>
              </div>
            </div>

            <div class="profileDivider"></div>

            <div class="profileSection">
              <div class="profileSection__title">Contact Information</div>

              <div class="profileGrid">
                <div class="profileRow">
                  <div class="profileKey">Email Address</div>
                  <div class="profileVal">
                    @if($email)
                      {{ $email }}
                    @else
                      <a href="#" class="profileAction" data-open-prof-modal="email">+ Add</a>
                    @endif
                  </div>
                </div>

                <div class="profileRow">
                  <div class="profileKey">Contact</div>
                  <div class="profileVal">
                    @if($phone)
                      {{ $phone }}
                      <a href="#" class="profileAction profileAction--edit" data-open-prof-modal="phone">✎ Edit</a>
                    @else
                      <a href="#" class="profileAction" data-open-prof-modal="phone">+ Add</a>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endif

      </section>
    </div>

    {{-- EMAIL MODAL --}}
    <div class="pModal" id="pModalEmail" aria-hidden="true">
      <div class="pModal__backdrop" data-close-prof-modal></div>

      <div class="pModal__panel" role="dialog" aria-modal="true">
        <button class="pModal__close" type="button" data-close-prof-modal aria-label="Close">×</button>

        <div class="pModal__title">Add Email Address</div>

        <form method="post" action="{{ route('profile.email.update') }}">
          @csrf

          <label class="pLabel">Email Address <span class="req">*</span></label>
          <input class="pInput" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required>

          <label class="pLabel">Email OTP <span class="req">*</span></label>
          <div class="pRow">
            <input class="pInput" type="text" name="email_otp" value="{{ old('email_otp') }}" placeholder="Enter Email OTP">
            <button class="pSend" type="button">Send</button>
          </div>

          <button class="pSubmit" type="submit">Submit</button>
        </form>
      </div>
    </div>

    {{-- PHONE MODAL --}}
    <div class="pModal" id="pModalPhone" aria-hidden="true">
      <div class="pModal__backdrop" data-close-prof-modal></div>

      <div class="pModal__panel" role="dialog" aria-modal="true">
        <button class="pModal__close" type="button" data-close-prof-modal aria-label="Close">×</button>

        <div class="pModal__title">{{ $phone ? 'Edit Contact' : 'Add Contact' }}</div>

        <form method="post" action="{{ route('profile.phone.update') }}">
          @csrf

          <label class="pLabel">Phone <span class="req">*</span></label>
          <div class="pRow">
            <input class="pInput pInput--cc" type="text" name="phone_country"
                   value="{{ old('phone_country', $u->phone_country ?? '+60') }}" placeholder="+60">
            <input class="pInput" type="text" name="phone" value="{{ old('phone', $u->phone ?? '') }}"
                   placeholder="Enter your phone number" required>
          </div>

          <label class="pLabel">Mobile OTP <span class="req">*</span></label>
          <div class="pRow">
            <input class="pInput" type="text" name="otp" value="{{ old('otp') }}" placeholder="Enter Mobile OTP">
            <button class="pSend" type="button">Send</button>
          </div>

          <button class="pSubmit" type="submit">Submit</button>
        </form>
      </div>
    </div>

    {{-- KYC MODAL --}}
    @include('partials.account_kyc_modal', ['banks' => $banks])

    {{-- OPEN MODAL ON VALIDATION ERROR --}}
    @if ($errors->any())
      <script>
        (function () {
          var hasEmailErr = {!! json_encode($errors->has('email') || $errors->has('email_otp')) !!};
          var hasPhoneErr = {!! json_encode($errors->has('phone') || $errors->has('otp') || $errors->has('phone_country')) !!};
          if (hasEmailErr) window.__OPEN_PROFILE_MODAL__ = 'email';
          if (hasPhoneErr) window.__OPEN_PROFILE_MODAL__ = 'phone';
        })();
      </script>
    @endif

  </main>
@endsection
