{{-- /home/lmh/app/resources/views/admins/partials/profile_modal.blade.php --}}

@php
  $has2fa = !empty($admin->two_fa_secret);
@endphp

<div class="lpf-wrap" id="lpfWrap" aria-hidden="false">
  <div class="lpf-backdrop" data-lpf-close="1"></div>

  <div class="lpf-modal" role="dialog" aria-modal="true" aria-label="Admin Profile">
    <div class="lpf-head">
      <div class="lpf-title">
        <div class="lpf-badge">👤</div>
        <div class="lpf-titletext">
          <div class="lpf-h1">Admin Profile</div>
          <div class="lpf-sub">{{ $admin->username ?? 'Admin' }}</div>
        </div>
      </div>

      <div class="lpf-headright">
        <div class="lpf-status">
          <span class="lpf-dot {{ $has2fa ? 'on' : '' }}" id="lpfStatusDot"></span>
          <span id="lpfStatusText">{{ $has2fa ? '2FA enabled' : '2FA disabled' }}</span>
        </div>
        <button class="lpf-x" type="button" data-lpf-close="1" aria-label="Close">✕</button>
      </div>
    </div>

    <div class="lpf-tabs" role="tablist" aria-label="Profile tabs">
      <button class="lpf-tab is-on" type="button" data-lpf-tab="pw" role="tab" aria-selected="true">Password</button>
      <button class="lpf-tab" type="button" data-lpf-tab="pin" role="tab" aria-selected="false">PIN</button>
      <button class="lpf-tab" type="button" data-lpf-tab="2fa" role="tab" aria-selected="false">2FA</button>
      <div class="lpf-spacer"></div>
      <div class="lpf-minihelp">Changes apply immediately</div>
    </div>

    <div class="lpf-body">
      <div class="lpf-alert" id="lpfAlert" style="display:none;"></div>

      {{-- PASSWORD --}}
      <section class="lpf-pane is-on" data-lpf-pane="pw">
        <div class="lpf-card">
          <div class="lpf-cardhead">
            <div class="lpf-cardtitle">Update password</div>
            <div class="lpf-cardhint">Minimum 8 chars</div>
          </div>

          <form id="lpfFormPw">
            @csrf

            <label class="lpf-label" for="lpfCurPw">Current password</label>
            <input class="lpf-input" id="lpfCurPw" type="password" name="current_password" autocomplete="current-password" required>

            <div class="lpf-row2">
              <div>
                <label class="lpf-label" for="lpfNewPw">New password</label>
                <input class="lpf-input" id="lpfNewPw" type="password" name="new_password" autocomplete="new-password" required>
              </div>
              <div>
                <label class="lpf-label" for="lpfNewPwC">Confirm new password</label>
                <input class="lpf-input" id="lpfNewPwC" type="password" name="new_password_confirmation" autocomplete="new-password" required>
              </div>
            </div>

            <div class="lpf-actions">
              <button class="lpf-btn" type="button" data-lpf-close="1">Cancel</button>
              <button class="lpf-btn lpf-btn-primary" type="submit">Save</button>
            </div>
          </form>
        </div>
      </section>

      {{-- PIN --}}
      <section class="lpf-pane" data-lpf-pane="pin">
        <div class="lpf-card">
          <div class="lpf-cardhead">
            <div class="lpf-cardtitle">Update PIN</div>
            <div class="lpf-cardhint">4–12 digits/characters</div>
          </div>

          <form id="lpfFormPin">
            @csrf

            <label class="lpf-label" for="lpfCurPw2">Current password</label>
            <input class="lpf-input" id="lpfCurPw2" type="password" name="current_password" autocomplete="current-password" required>

            <div class="lpf-row2">
              <div>
                <label class="lpf-label" for="lpfNewPin">New PIN</label>
                <input class="lpf-input" id="lpfNewPin" type="password" name="new_pin" inputmode="numeric" required>
              </div>
              <div>
                <label class="lpf-label" for="lpfNewPinC">Confirm PIN</label>
                <input class="lpf-input" id="lpfNewPinC" type="password" name="new_pin_confirmation" inputmode="numeric" required>
              </div>
            </div>

            <div class="lpf-actions">
              <button class="lpf-btn" type="button" data-lpf-close="1">Cancel</button>
              <button class="lpf-btn lpf-btn-primary" type="submit">Save</button>
            </div>
          </form>
        </div>
      </section>

      {{-- 2FA --}}
      <section class="lpf-pane" data-lpf-pane="2fa">
        <div class="lpf-card">
          <div class="lpf-cardhead">
            <div class="lpf-cardtitle">2FA Secret</div>
            <div class="lpf-cardhint">Set / update / clear</div>
          </div>

          <form id="lpfForm2fa">
            @csrf
            <input type="hidden" name="action" value="set">

            <label class="lpf-label" for="lpfCurPw3">Current password</label>
            <input class="lpf-input" id="lpfCurPw3" type="password" name="current_password" autocomplete="current-password" required>

            <label class="lpf-label" for="lpf2faSecret">2FA Secret</label>
            <input class="lpf-input" id="lpf2faSecret" type="text" name="two_fa_secret" value="{{ $admin->two_fa_secret ?? '' }}" placeholder="paste secret here (blank = disable)">

            <div class="lpf-actions">
              <button class="lpf-btn" type="button" data-lpf-close="1">Cancel</button>
              <button class="lpf-btn lpf-btn-primary" type="submit">Save</button>
            </div>
          </form>

          <div class="lpf-divider"></div>

          <form id="lpfForm2faDisable">
            @csrf
            <input type="hidden" name="action" value="disable">

            <label class="lpf-label" for="lpfCurPw4">Current password (disable)</label>
            <input class="lpf-input" id="lpfCurPw4" type="password" name="current_password" autocomplete="current-password" required>

            <div class="lpf-actions">
              <button class="lpf-btn lpf-btn-danger" type="submit">Disable 2FA</button>
            </div>
          </form>
        </div>
      </section>
    </div>
  </div>
</div>
