<div class="pModal" id="pModalWithdrawAddAccount" aria-hidden="true">
  <div class="pModal__backdrop" data-close-prof-modal></div>

  <div class="pModal__panel" role="dialog" aria-modal="true">
    <button class="pModal__close" type="button" data-close-prof-modal aria-label="Close">×</button>

    <div class="pModal__title">Add Bank Account</div>

    <form method="post" action="{{ route('profile.bank.store') }}">
      @csrf

      <label class="pLabel">Bank Name <span class="req">*</span></label>
      <select class="pInput" name="bank_name" required>
        <option value="" disabled selected>Select a bank</option>
        @foreach ($banks as $b)
          <option value="{{ $b }}" {{ old('bank_name')===$b ? 'selected' : '' }}>{{ $b }}</option>
        @endforeach
      </select>

      <label class="pLabel">Account Holder Name <span class="req">*</span></label>
      <input class="pInput" type="text" name="account_holder_name"
             value="{{ old('account_holder_name') }}" maxlength="191" required>

      <label class="pLabel">Account Number <span class="req">*</span></label>
      <input class="pInput" type="text" name="account_number"
             value="{{ old('account_number') }}" maxlength="64" inputmode="numeric" required>

      <button class="pSubmit" type="submit" style="margin-top: 12px;">Save</button>
    </form>
  </div>
</div>