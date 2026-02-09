{{-- /home/lmh/app/resources/views/partials/account_kyc_section.blade.php --}}
@php
  $kyc = $latestKyc ?? null;
  $status = $kyc?->status;
@endphp

<div class="kycCard">

  {{-- APPROVED --}}
  @if($kyc && $status === 'approved')
    <div class="kycProgress">
      <div style="font-size:42px; margin: 6px 0 8px;">✅</div>
      <div class="kycProgress__title" style="color: rgba(199,255,0,1);">Identity Verification Approved</div>
      <div class="kycProgress__sub">
        Your account is now fully verified and secured. You have complete access to all account features.
      </div>
    </div>

    <div class="kycInfo">
      <div class="kycRow">
        <div class="kycKey">Account Name</div>
        <div class="kycVal">{{ $kyc->account_holder_name }}</div>
      </div>
      <div class="kycRow">
        <div class="kycKey">Bank Name</div>
        <div class="kycVal">{{ $kyc->bank_name }}</div>
      </div>
      <div class="kycRow">
        <div class="kycKey">Account Number</div>
        <div class="kycVal">{{ $kyc->account_number }}</div>
      </div>
      <div class="kycRow">
        <div class="kycKey">Verification Status</div>
        <div class="kycVal"><span class="kycDot" style="background:#23d3a4;"></span> Success</div>
      </div>
    </div>

  {{-- PENDING --}}
  @elseif($kyc && $status === 'pending')
    <div class="kycProgress">
      <div class="kycProgress__ring"></div>
      <div class="kycProgress__title">Verification in Progress</div>
      <div class="kycProgress__sub">
        We’re carefully reviewing your submitted details. This may take 5–10 minutes.
        You can still access basic platform features in the meantime.
      </div>
    </div>

    <div class="kycInfo">
      <div class="kycRow">
        <div class="kycKey">Account Name</div>
        <div class="kycVal">{{ $kyc->account_holder_name }}</div>
      </div>
      <div class="kycRow">
        <div class="kycKey">Bank Name</div>
        <div class="kycVal">{{ $kyc->bank_name }}</div>
      </div>
      <div class="kycRow">
        <div class="kycKey">Account Number</div>
        <div class="kycVal">{{ $kyc->maskedAccountNumber() }}</div>
      </div>
      <div class="kycRow">
        <div class="kycKey">Verification Status</div>
        <div class="kycVal"><span class="kycDot"></span> In Progress</div>
      </div>

      @if($kyc->remarks)
        <div class="kycRow">
          <div class="kycKey">Remarks</div>
          <div class="kycVal">{{ $kyc->remarks }}</div>
        </div>
      @endif
    </div>

    <form method="post" action="{{ route('kyc.cancel') }}">
      @csrf
      <button class="kycCancel" type="submit">Cancel</button>
      <div class="kycDangerNote">This action is irreversible. Once cancelled, it cannot be undone.</div>
    </form>

  {{-- REJECTED --}}
  @elseif($kyc && $status === 'rejected')
    <div class="kycEmpty">
      <div class="kycEmpty__title">Identity Verification</div>
      <div class="kycEmpty__sub">Your submission was rejected. Please resubmit with correct details.</div>

      @if($kyc->remarks)
        <div class="kycErr">{{ $kyc->remarks }}</div>
      @endif

      <button class="kycStart" type="button" data-open-kyc-modal>Resubmit Verification</button>
    </div>

  {{-- EMPTY / CANCELLED / NONE --}}
  @else
    <div class="kycEmpty">
      <div class="kycEmpty__title">Identity Verification</div>
      <div class="kycEmpty__sub">Submit your bank details to start verification.</div>

      @if($errors->has('kyc'))
        <div class="kycErr">{{ $errors->first('kyc') }}</div>
      @endif

      <button class="kycStart" type="button" data-open-kyc-modal>Start Verification</button>
    </div>
  @endif
</div>
