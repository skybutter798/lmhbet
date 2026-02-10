@extends('layouts.app')

@section('body')
  @include('partials.header')

  <main class="accPage">
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'profile', 'activeSub' => 'bank_details'])

      <section class="accMain">
        <h1 style="margin:0 0 12px;">Bank Details</h1>

        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul style="margin:0; padding-left:18px;">
              @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="profileCard">
          <div class="profileSection">
            <div class="profileSection__title" style="display:flex; align-items:center; justify-content:space-between;">
              <span>Withdrawal Bank Accounts</span>

              <a href="#" class="profileAction"
                 data-open-withdraw-add-account>
                + Add Account
              </a>
            </div>

            @if ($accounts->isEmpty())
              <p style="margin: 10px 0 0;">No bank accounts added yet.</p>
            @else
              <div class="profileGrid" style="margin-top: 10px;">
                @foreach ($accounts as $acc)
                  <div class="profileRow" style="align-items:flex-start;">
                    <div class="profileKey" style="min-width:160px;">
                      {{ $acc->bank_name }}
                      @if ((int)$defaultId === (int)$acc->id)
                        <div style="font-size:12px; margin-top:4px;">
                          <span class="badge">Default</span>
                        </div>
                      @endif
                    </div>

                    <div class="profileVal" style="flex:1;">
                      <div><strong>{{ $acc->account_holder_name }}</strong></div>
                      <div>{{ $acc->maskedAccountNumber() }}</div>

                      <div style="margin-top:8px; display:flex; gap:10px; flex-wrap:wrap;">
                        @if ((int)$defaultId !== (int)$acc->id)
                          <form method="post" action="{{ route('profile.bank.default', $acc->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm">Set Default</button>
                          </form>
                        @endif

                        <form method="post" action="{{ route('profile.bank.destroy', $acc->id) }}"
                              onsubmit="return confirm('Remove this bank account?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                        </form>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>

      </section>
    </div>

    {{-- Add Account Modal (account.js already supports this id) --}}
    @include('partials.account_withdraw_add_account_modal', ['banks' => $banks])

    {{-- Open modal on validation error (optional) --}}
    @if ($errors->any())
      <script>
        (function () {
          window.__OPEN_PROFILE_MODAL__ = 'withdrawAddAccount';
        })();
      </script>
    @endif

  </main>
@endsection