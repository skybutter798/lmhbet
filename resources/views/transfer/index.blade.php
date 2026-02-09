{{-- /home/lmh/app/resources/views/transfer/index.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
  @endphp

  <main class="accPage">

    {{-- =========================
        MOBILE (Transfer page)
        - show same Transfer form + History
        - hide sidebar & desktop layout
        ========================= --}}
    <section class="accMobile">
      <div class="wrap">

        {{-- Mobile header + wallet summary --}}
        <div class="mProfile">
          <div class="mProfile__head">
            <a class="mBack" href="{{ route('profile.index') }}">← Back</a>
            <div class="mProfile__title">Transfer</div>
          </div>

            @include('partials.account_mobile_dashboard', [
              'currency' => $currency,
              'cash' => $cash,
              'chips' => $chips,
              'bonus' => $bonus,
              'fmt' => $fmt,
            ])


          {{-- Tabs --}}
          <div class="tfTabs">
            <button class="tfTab" type="button" disabled>Receive</button>
            <button class="tfTab is-active" type="button">Transfer</button>
          </div>

          {{-- Form --}}
          <div class="tfCard tfCard--m">
            <form method="post" action="{{ route('transfer.store') }}" class="tfForm tfForm--m">
              @csrf

              <label class="tfLabel">Transfer To <span class="req">*</span></label>
              <input class="tfInput" type="text" name="to_username" value="{{ old('to_username') }}" placeholder="Search by username" required>

              <label class="tfLabel">Wallet Type <span class="req">*</span></label>
              <select class="tfInput" name="wallet_type" required>
                @foreach($walletOptions as $k => $label)
                  <option value="{{ $k }}" {{ old('wallet_type','main')===$k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>

              <label class="tfLabel">Amount <span class="req">*</span></label>
              <input class="tfInput" type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" placeholder="Enter or select an amount to transfer" required>

              <div class="tfQuick">
                @foreach([10,20,50,100,500,1000] as $q)
                  <button class="tfQuickBtn" type="button" data-q="{{ $q }}">{{ $q >= 1000 ? '1k' : $q }}</button>
                @endforeach
              </div>

              <label class="tfLabel">Remarks</label>
              <textarea class="tfTextarea" name="description" maxlength="200" placeholder="Add a remark">{{ old('description') }}</textarea>
              <div class="tfCount"><span data-remark-count>0</span>/200</div>

              <button class="tfSubmit" type="submit">Transfer</button>

              @if ($errors->any())
                <div class="tfErr">
                  <ul>
                    @foreach($errors->all() as $err)
                      <li>{{ $err }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </form>
          </div>

          {{-- History --}}
          <div class="tfHistory tfHistory--m">
            <div class="tfHistory__title">Transfer History (Today)</div>

            <div class="tfTable tfTable--m">
              <div class="tfHead tfHead--m">
                <div>Date</div>
                <div>From</div>
                <div>To</div>
                <div class="tRight">Amount</div>
                <div>Remark</div>
              </div>

              <div class="tfBody">
                @if($txToday->count())
                  @foreach($txToday as $tx)
                    @php
                      $from = $tx->meta['from_username'] ?? ($tx->direction==='debit' ? auth()->user()->username : '-');
                      $to   = $tx->meta['to_username'] ?? ($tx->direction==='credit' ? auth()->user()->username : '-');
                    @endphp

                    <div class="tfRow tfRow--m">
                      <div>{{ optional($tx->occurred_at)->format('Y-m-d H:i') ?? $tx->created_at->format('Y-m-d H:i') }}</div>
                      <div>{{ $from }}</div>
                      <div>{{ $to }}</div>
                      <div class="tRight">{{ $fmt($tx->amount) }}</div>
                      <div>{{ $tx->description }}</div>
                    </div>
                  @endforeach
                @else
                  <div class="tfEmpty">
                    <div class="tfEmpty__ico">🗂️</div>
                    <div class="tfEmpty__title">No Data</div>
                    <div class="tfEmpty__sub">No items found.</div>
                  </div>
                @endif
              </div>
            </div>
          </div>

        </div>

      </div>
    </section>

    {{-- =========================
        DESKTOP (existing)
        ========================= --}}
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'funds', 'activeSub' => 'transfer'])

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
        <div class="tfTabs">
          <button class="tfTab" type="button" disabled>Receive</button>
          <button class="tfTab is-active" type="button">Transfer</button>
        </div>

        {{-- FORM --}}
        <div class="tfCard">
          <form method="post" action="{{ route('transfer.store') }}" class="tfForm">
            @csrf

            <label class="tfLabel">Transfer To <span class="req">*</span></label>
            <input class="tfInput" type="text" name="to_username" value="{{ old('to_username') }}" placeholder="Search by username" required>

            <label class="tfLabel">Wallet Type <span class="req">*</span></label>
            <select class="tfInput" name="wallet_type" required>
              @foreach($walletOptions as $k => $label)
                <option value="{{ $k }}" {{ old('wallet_type','main')===$k ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>

            <label class="tfLabel">Amount <span class="req">*</span></label>
            <input class="tfInput" type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" placeholder="Enter or select an amount to transfer" required>

            <div class="tfQuick">
              @foreach([10,20,50,100,500,1000] as $q)
                <button class="tfQuickBtn" type="button" data-q="{{ $q }}">{{ $q >= 1000 ? '1k' : $q }}</button>
              @endforeach
            </div>

            <label class="tfLabel">Remarks</label>
            <textarea class="tfTextarea" name="description" maxlength="200" placeholder="Add a remark">{{ old('description') }}</textarea>
            <div class="tfCount"><span data-remark-count>0</span>/200</div>

            <button class="tfSubmit" type="submit">Transfer</button>

            @if ($errors->any())
              <div class="tfErr">
                <ul>
                  @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
          </form>
        </div>

        {{-- HISTORY --}}
        <div class="tfHistory">
          <div class="tfHistory__title">Transfer History (Today)</div>

          <div class="tfTable">
            <div class="tfHead">
              <div>Transaction Date</div>
              <div>From</div>
              <div>To</div>
              <div class="tRight">Amount</div>
              <div>Remark</div>
            </div>

            <div class="tfBody">
              @if($txToday->count())
                @foreach($txToday as $tx)
                  @php
                    $from = $tx->meta['from_username'] ?? ($tx->direction==='debit' ? auth()->user()->username : '-');
                    $to   = $tx->meta['to_username'] ?? ($tx->direction==='credit' ? auth()->user()->username : '-');
                  @endphp

                  <div class="tfRow">
                    <div>{{ optional($tx->occurred_at)->format('Y-m-d H:i') ?? $tx->created_at->format('Y-m-d H:i') }}</div>
                    <div>{{ $from }}</div>
                    <div>{{ $to }}</div>
                    <div class="tRight">{{ $fmt($tx->amount) }}</div>
                    <div>{{ $tx->description }}</div>
                  </div>
                @endforeach
              @else
                <div class="tfEmpty">
                  <div class="tfEmpty__ico">🗂️</div>
                  <div class="tfEmpty__title">No Data</div>
                  <div class="tfEmpty__sub">No items found. Please try a different search.</div>
                </div>
              @endif
            </div>
          </div>
        </div>

      </section>
    </div>
  </main>

  <script>
    (function () {
      function setAmount(v) {
        var inp = document.querySelector('input[name="amount"]');
        if (!inp) return;
        inp.value = v;
        inp.dispatchEvent(new Event('input', { bubbles: true }));
      }

      function updateCount() {
        var ta = document.querySelector('textarea[name="description"]');
        var out = document.querySelector('[data-remark-count]');
        if (!ta || !out) return;
        out.textContent = String(ta.value.length);
      }

      document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-q]');
        if (!b) return;
        e.preventDefault();
        setAmount(b.getAttribute('data-q'));
      });

      document.addEventListener('input', function (e) {
        if (e.target && e.target.matches('textarea[name="description"]')) {
          updateCount();
        }
      });

      updateCount();
    })();
  </script>
@endsection
