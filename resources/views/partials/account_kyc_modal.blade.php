{{-- resources/views/partials/account_kyc_modal.blade.php --}}
<div class="pModal" id="pModalKyc" aria-hidden="true">
  <div class="pModal__backdrop" data-close-prof-modal></div>

  <div class="pModal__panel" role="dialog" aria-modal="true">
    <button class="pModal__close" type="button" data-close-prof-modal aria-label="Close">×</button>

    <div class="pModal__title">Identity Verification</div>

    <form method="post" action="{{ route('kyc.submit') }}">
      @csrf

      <label class="pLabel">Bank <span class="req">*</span></label>
      <select class="pInput" name="bank_name" required>
        <option value="" disabled {{ old('bank_name') ? '' : 'selected' }}>Select Bank</option>
        @foreach($banks as $b)
          <option value="{{ $b }}" {{ old('bank_name') === $b ? 'selected' : '' }}>{{ $b }}</option>
        @endforeach
      </select>

      <label class="pLabel">Full Name (Bank Account Holder) <span class="req">*</span></label>
      <input class="pInput" type="text" name="account_holder_name"
             value="{{ old('account_holder_name') }}" placeholder="Enter full name" required>

      <label class="pLabel">Bank Account Number <span class="req">*</span></label>
      <input class="pInput" type="text" name="account_number"
             value="{{ old('account_number') }}" placeholder="Enter account number" required>

      <button class="pSubmit" type="submit">Submit</button>
    </form>
  </div>
</div>

@if ($errors->has('bank_name') || $errors->has('account_holder_name') || $errors->has('account_number') || $errors->has('kyc'))
  <script>
    window.__OPEN_PROFILE_MODAL__ = window.__OPEN_PROFILE_MODAL__ || 'kyc';
  </script>
@endif
