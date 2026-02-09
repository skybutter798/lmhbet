{{-- partials/auth-modal.blade.php --}}

{{-- AUTH MODAL --}}
<div class="modal" id="authModal" aria-hidden="true">
  <div class="modal__backdrop" data-close-modal></div>

  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="authTitle">
    <button class="modal__close" type="button" aria-label="Close" data-close-modal>×</button>

    <div class="modal__head">
      <h3 id="authTitle" class="modal__title">Sign In</h3>
      <p class="modal__sub">Use your username and password.</p>
    </div>

    <div class="modalTabs" data-auth-tabs>
      <button class="modalTab is-active" type="button" data-auth-tab="login">Sign In</button>
      <button class="modalTab" type="button" data-auth-tab="register">Create Account</button>
    </div>

    {{-- LOGIN FORM --}}
    <form class="modalForm is-active" data-auth-pane="login" action="{{ route('login.store') }}" method="post" autocomplete="on">
      @csrf
      <input type="hidden" name="_form" value="login">

      <label class="fLabel">Username <span class="req">*</span></label>
      <input class="fInput" name="username" type="text" placeholder="Enter your username" required>

      <label class="fLabel">Password <span class="req">*</span></label>
      <div class="fRow">
        <input class="fInput" name="password" type="password" placeholder="Enter your password" required>
        <button class="eyeBtn" type="button" data-toggle-pass aria-label="Show password">👁</button>
      </div>

      <div class="fBetween">
        <label class="chk">
          <input type="checkbox" name="remember">
          <span>Remember Me</span>
        </label>
        <a class="smallLink" href="#">Forgot your password?</a>
      </div>

      <button class="btn btn--primary btn--full" type="submit">Submit</button>

      <div class="modalFoot">
        <span class="muted">Don’t have an account?</span>
        <button class="smallLinkBtn" type="button" data-open-modal="register">Create Account</button>
      </div>
    </form>

    {{-- REGISTER FORM --}}
    <form class="modalForm" data-auth-pane="register" action="{{ route('register.store') }}" method="post" autocomplete="on">
      @csrf

      <input type="hidden" name="_form" value="register">
      <input type="hidden" name="country" value="MY">
      <input type="hidden" name="phone_country" value="+60">
      <input type="hidden" name="otp_type" value="mobile">

      @if ($errors->any())
        <div class="alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <label class="fLabel">Username <span class="req">*</span></label>
      <input class="fInput" name="username" type="text" value="{{ old('username') }}" placeholder="Username" required>

      <label class="fLabel">Password <span class="req">*</span></label>
      <div class="fRow">
        <input class="fInput" name="password" type="password" placeholder="Password" required>
        <button class="eyeBtn" type="button" data-toggle-pass aria-label="Show password">👁</button>
      </div>

      <label class="fLabel">Confirm Password <span class="req">*</span></label>
      <input class="fInput" name="password_confirmation" type="password" placeholder="Confirm Password" required>

      <label class="fLabel">Referral Code</label>
      <label class="fLabel">Referral Code</label> <input class="fInput" name="referral_code" type="text" value="{{ old('referral_code', request('ref')) }}" placeholder="LMH">

      <div class="otpTabs">
        <button class="otpTab is-active" type="button" data-otp-tab="mobile">Mobile OTP</button>
        <button class="otpTab" type="button" data-otp-tab="email">Email OTP</button>
      </div>

      <div class="otpPane is-active" data-otp-pane="mobile">
        <div class="phoneRow">
          <button class="countryBtn" type="button">🇲🇾 <span>+60</span> ▾</button>
          <input class="fInput" type="text" name="phone" value="{{ old('phone') }}" placeholder="Enter your phone number">
        </div>

        <div class="otpRow">
          <input class="fInput" type="text" name="otp" placeholder="Enter Mobile OTP">
          <button class="sendBtn" type="button">Send</button>
        </div>
      </div>

      <div class="otpPane" data-otp-pane="email">
        <div class="otpRow">
          <input class="fInput" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email">
          <button class="sendBtn" type="button">Send</button>
        </div>
        <input class="fInput" type="text" name="email_otp" placeholder="Enter Email OTP">
      </div>

      <label class="fLabel">Currency</label>
      <select class="fInput" name="currency">
        <option value="MYR" {{ old('currency','MYR')==='MYR' ? 'selected' : '' }}>Malaysian Ringgit (MYR)</option>
        <option value="SGD" {{ old('currency')==='SGD' ? 'selected' : '' }}>Singapore Dollar (SGD)</option>
        <option value="USD" {{ old('currency')==='USD' ? 'selected' : '' }}>US Dollar (USD)</option>
      </select>

      <button class="btn btn--primary btn--full" type="submit">Register</button>

      <div class="terms">
        <span class="muted">By signing up you accept the</span>
        <a href="#" class="smallLink">terms &amp; conditions</a>.
      </div>

      <div class="modalFoot">
        <span class="muted">Already have an account?</span>
        <button class="smallLinkBtn" type="button" data-open-modal="login">Login</button>
      </div>
    </form>

  </div>
</div>

{{-- Auto-open modal on validation errors / referral --}}
@if ($errors->any() && old('_form') === 'login')
  <script>window.__OPEN_AUTH_MODAL__='login';</script>
@endif
@if ($errors->any() && old('_form') === 'register')
  <script>window.__OPEN_AUTH_MODAL__='register';</script>
@endif
@if (!$errors->any() && request()->filled('ref'))
  <script>window.__OPEN_AUTH_MODAL__='register';</script>
@endif
