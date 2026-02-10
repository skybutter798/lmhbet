{{-- /home/lmh/app/resources/views/withdrawals/index.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
    $u = auth()->user();

    $hasAccount = isset($bankAccounts) && $bankAccounts->count() > 0;

    $defaultId = $defaultBankAccount?->id ?? null;

    $selectedBankId = old('bank_account_id')
      ?? ($defaultId ?: ($hasAccount ? $bankAccounts->first()->id : null));
  @endphp

  <main class="accPage">

    <section class="accMobile">
      <div class="wrap">

        @include('partials.account_mobile_dashboard', [
          'currency' => $currency,
          'cash' => $cash,
          'chips' => $chips,
          'bonus' => $bonus,
          'fmt' => $fmt,
        ])

        <div class="wdCard" data-withdraw>
          <div class="wdHead">
            <div class="wdTitle">Withdrawal</div>
            <button class="wdAddAcc" type="button" data-open-withdraw-add-account>+ Add Account</button>
          </div>

          @if(session('success'))
            <div class="wdOk">{{ session('success') }}</div>
          @endif

          @if($errors->has('withdraw'))
            <div class="wdErr">{{ $errors->first('withdraw') }}</div>
          @endif

          <form method="post" action="{{ route('withdraw.store') }}" class="wdForm">
            @csrf

            <label class="wdLabel">Bank Account <span class="req">*</span></label>
            <div class="wdSelectRow">
              <select class="wdSelect" name="bank_account_id" {{ $hasAccount ? '' : 'disabled' }} required>
                @if(!$hasAccount)
                  <option selected>No bank account</option>
                @else
                  @foreach($bankAccounts as $acc)
                    @php
                      $isDefault = ((int)$defaultId === (int)$acc->id);
                      $label = $acc->maskedAccountNumber() . ' - ' . $acc->bank_name . ($isDefault ? ' (Default)' : '');
                    @endphp
                    <option value="{{ $acc->id }}" {{ (string)$selectedBankId === (string)$acc->id ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                @endif
              </select>
            </div>

            <label class="wdLabel">Withdraw Amount <span class="req">*</span></label>
            <div class="wdNoteLine">Available Cash: {{ $currency }} {{ $fmt($cash) }}</div>

            <input
              class="wdAmount"
              type="text"
              inputmode="decimal"
              name="amount"
              value="{{ old('amount') }}"
              placeholder="MIN: {{ number_format($minWithdraw, 2) }} / MAX: {{ number_format($maxWithdraw, 2) }}"
              {{ $hasAccount ? '' : 'disabled' }}
              required
              data-withdraw-amount
            />
            @if($errors->has('amount'))
              <div class="wdFieldErr">{{ $errors->first('amount') }}</div>
            @endif

            <div class="wdNoteLine">Daily Count Balance:3</div>

            <div class="wdQuick" data-withdraw-quick>
              <button class="wdQuickBtn" type="button" data-amt="100">100</button>
              <button class="wdQuickBtn" type="button" data-amt="500">500</button>
              <button class="wdQuickBtn" type="button" data-amt="1000">1k</button>
              <button class="wdQuickBtn" type="button" data-amt="5000">5k</button>
              <button class="wdQuickBtn" type="button" data-amt="10000">10k</button>
              <button class="wdQuickBtn" type="button" data-amt="20000">20k</button>
            </div>

            <button class="wdSubmit" type="submit" {{ $hasAccount ? '' : 'disabled' }}>Submit</button>

            @if(!$hasAccount)
              <div class="wdHint">
                Add a bank account before withdrawing.
                <a class="wdLink" href="{{ route('profile.bank') }}">Manage bank accounts</a>
              </div>
            @endif
          </form>
        </div>

        <div class="wdNotice">
          <div class="wdNoticeHead">
            <div class="wdNoticeTitle">⚠ Important Notice</div>
            <button class="wdNoticeToggle" type="button" data-withdraw-notice-toggle>Show</button>
          </div>
          <div class="wdNoticeBody" data-withdraw-notice-body>
            <ul class="wdNoticeList">
              <li>The bank account name for withdrawals must match the full name registered on your account.</li>
              <li>Members are not allowed to withdraw to third-party bank accounts.</li>
              <li>If you have any questions regarding withdrawals, please contact our 24/7 LIVECHAT. Thank you.</li>
            </ul>
          </div>
        </div>

        <div class="wdHistory">
          <div class="wdHistoryTitle">🕘 Withdrawal History</div>
          <div class="wdHistorySub">Transaction</div>

          @if(isset($history) && $history->count())
            <div class="wdHistList">
              @foreach($history as $row)
                <div class="wdHistRow">
                  <div class="wdHistLeft">
                    <div class="wdHistAmt">{{ $currency }} {{ $fmt($row->amount) }}</div>
                    <div class="wdHistMeta">{{ $row->created_at->format('Y-m-d H:i') }}</div>
                  </div>
                  <div class="wdHistRight">
                    <span class="wdStatus wdStatus--{{ $row->status }}">{{ ucfirst($row->status) }}</span>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="wdPagination">
              {{ $history->links() }}
            </div>
          @else
            <div class="wdEmpty">
              <div class="wdEmptyIco">📄</div>
              <div class="wdEmptyTitle">No Data</div>
              <div class="wdEmptySub">No items found.</div>
            </div>
          @endif
        </div>

      </div>
    </section>

    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'funds', 'activeSub' => 'withdraw'])

      <section class="accMain">
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

        <div class="wdDesktopGrid">
          <div>
            <div class="wdCard wdCard--desk" data-withdraw>
              <div class="wdHead">
                <div class="wdTitle">Withdrawal</div>
                <button class="wdAddAcc" type="button" data-open-withdraw-add-account>+ Add Account</button>
              </div>

              @if(session('success'))
                <div class="wdOk">{{ session('success') }}</div>
              @endif

              @if($errors->has('withdraw'))
                <div class="wdErr">{{ $errors->first('withdraw') }}</div>
              @endif

              <form method="post" action="{{ route('withdraw.store') }}" class="wdForm">
                @csrf

                <label class="wdLabel">Bank Account <span class="req">*</span></label>
                <select class="wdSelect" name="bank_account_id" {{ $hasAccount ? '' : 'disabled' }} required>
                  @if(!$hasAccount)
                    <option selected>No bank account</option>
                  @else
                    @foreach($bankAccounts as $acc)
                      @php
                        $isDefault = ((int)$defaultId === (int)$acc->id);
                        $label = $acc->maskedAccountNumber() . ' - ' . $acc->bank_name . ($isDefault ? ' (Default)' : '');
                      @endphp
                      <option value="{{ $acc->id }}" {{ (string)$selectedBankId === (string)$acc->id ? 'selected' : '' }}>
                        {{ $label }}
                      </option>
                    @endforeach
                  @endif
                </select>

                <label class="wdLabel">Withdraw Amount <span class="req">*</span></label>
                <div class="wdNoteLine">Available Cash: {{ $currency }} {{ $fmt($cash) }}</div>

                <input
                  class="wdAmount"
                  type="text"
                  inputmode="decimal"
                  name="amount"
                  value="{{ old('amount') }}"
                  placeholder="MIN: {{ number_format($minWithdraw, 2) }} / MAX: {{ number_format($maxWithdraw, 2) }}"
                  {{ $hasAccount ? '' : 'disabled' }}
                  required
                  data-withdraw-amount
                />
                @if($errors->has('amount'))
                  <div class="wdFieldErr">{{ $errors->first('amount') }}</div>
                @endif

                <div class="wdQuick" data-withdraw-quick>
                  <button class="wdQuickBtn" type="button" data-amt="100">100</button>
                  <button class="wdQuickBtn" type="button" data-amt="500">500</button>
                  <button class="wdQuickBtn" type="button" data-amt="1000">1k</button>
                  <button class="wdQuickBtn" type="button" data-amt="5000">5k</button>
                  <button class="wdQuickBtn" type="button" data-amt="10000">10k</button>
                  <button class="wdQuickBtn" type="button" data-amt="20000">20k</button>
                </div>

                <button class="wdSubmit" type="submit" {{ $hasAccount ? '' : 'disabled' }}>Submit</button>

                @if(!$hasAccount)
                  <div class="wdHint">
                    Add a bank account before withdrawing.
                    <a class="wdLink" href="{{ route('profile.bank') }}">Manage bank accounts</a>
                  </div>
                @endif
              </form>
            </div>

            <div class="wdNotice wdNotice--desk">
              <div class="wdNoticeHead">
                <div class="wdNoticeTitle">⚠ Important Notice</div>
                <button class="wdNoticeToggle" type="button" data-withdraw-notice-toggle>Show</button>
              </div>
              <div class="wdNoticeBody" data-withdraw-notice-body>
                <ul class="wdNoticeList">
                  <li>The bank account name for withdrawals must match the full name registered on your account.</li>
                  <li>Members are not allowed to withdraw to third-party bank accounts.</li>
                  <li>If you have any questions regarding withdrawals, please contact our 24/7 LIVECHAT. Thank you.</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="wdHistory wdHistory--desk">
            <div class="wdHistoryTitle">🕘 Withdrawal History</div>
            <div class="wdHistorySub">Transaction</div>

            @if(isset($history) && $history->count())
              <div class="wdHistList">
                @foreach($history as $row)
                  <div class="wdHistRow">
                    <div class="wdHistLeft">
                      <div class="wdHistAmt">{{ $currency }} {{ $fmt($row->amount) }}</div>
                      <div class="wdHistMeta">{{ $row->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="wdHistRight">
                      <span class="wdStatus wdStatus--{{ $row->status }}">{{ ucfirst($row->status) }}</span>
                    </div>
                  </div>
                @endforeach
              </div>

              <div class="wdPagination">
                {{ $history->links() }}
              </div>
            @else
              <div class="wdEmpty">
                <div class="wdEmptyIco">📄</div>
                <div class="wdEmptyTitle">No Data</div>
                <div class="wdEmptySub">No items found.</div>
              </div>
            @endif
          </div>
        </div>

      </section>
    </div>

    @include('partials.account_withdraw_add_account_modal', ['banks' => $banks])

    @if ($errors->any() && ($errors->has('bank_name') || $errors->has('account_holder_name') || $errors->has('account_number')))
      <script>
        (function () {
          window.__OPEN_PROFILE_MODAL__ = 'withdrawAddAccount';
        })();
      </script>
    @endif

  </main>
@endsection