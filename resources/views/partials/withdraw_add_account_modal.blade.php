{{-- /home/lmh/app/resources/views/partials/withdraw_add_account_modal.blade.php --}}
<div class="pModal" id="pModalWithdrawAddAccount" aria-hidden="true">
  <div class="pModal__backdrop" data-close-prof-modal></div>

  <div class="pModal__panel" role="dialog" aria-modal="true">
    <button class="pModal__close" type="button" data-close-prof-modal aria-label="Close">×</button>

    <div class="pModal__title">Add Account</div>

    <div class="wdModalText">
      Bank accounts used for withdrawal come from your Identity Verification.
    </div>

    <a class="pSubmit" href="{{ route('profile.index', ['m' => 'profile', 'kyc' => true]) }}" style="text-decoration:none; display:block; text-align:center;">
      Go to Identity Verification
    </a>
  </div>
</div>
