{{-- /home/lmh/app/resources/views/deposits/index.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
    $u = auth()->user();
  @endphp

  <main class="accPage">

    <section class="accMobile">
      <div class="wrap">

        <div class="dCard">
          @if(session('success'))
            <div class="dFlash">{{ session('success') }}</div>
          @endif

          @if($errors->any())
            <div class="dFlash dFlash--error">
              {{ $errors->first() }}
            </div>
          @endif

          @include('deposits._form')
        </div>

        <div class="dNotice">
          <button class="dNotice__head" type="button" data-dep-notice-toggle aria-expanded="false">
            <span>⚠ Important Notice</span>
            <span class="dNotice__toggle">Show</span>
          </button>

          <div class="dNotice__body" data-dep-notice-body>
            <ul class="dNotice__list">
              <li>Always check the latest active deposit details before making a deposit.</li>
              <li>Cash deposits are not accepted.</li>
              <li>Account name should match your registered name.</li>
              <li>Do not include sensitive words in online transfer remarks.</li>
              <li>Support is available via live chat if pending too long.</li>
            </ul>
          </div>
        </div>

        <div class="dHistory">
          <div class="dHistory__title">🕒 Deposit History (Today)</div>
          <div class="dHistory__sub">Transaction</div>

          @if($history->isEmpty())
            <div class="dHistoryEmpty">
              <div class="dHistoryEmpty__ico">📦</div>
              <div class="dHistoryEmpty__title">No Data</div>
              <div class="dHistoryEmpty__sub">No items found. Please try a different search.</div>
            </div>
          @else
            <div class="dHistList">
              @foreach($history as $h)
                <div class="dHistItem">
                  <div class="dHistTop">
                    <div class="dHistBank">
                      @if($h->method === 'bank_transfer')
                        {{ $h->bank_name ?: '-' }}
                      @elseif($h->method === 'e_wallet')
                        @if($h->provider === 'vpay')
                          E-Wallet (VPay)
                        @elseif($h->provider === 'winpay')
                          E-Wallet (WinPay)
                        @else
                          E-Wallet
                        @endif
                      @else
                        {{ ucfirst(str_replace('_', ' ', $h->method)) }}
                      @endif
                    </div>
                    <div class="dHistStatus {{ $h->status === 'pending' ? 'is-pending' : '' }}">
                      {{ $h->status === 'pending' ? 'In Progress' : ucfirst($h->status) }}
                    </div>
                  </div>

                  <div class="dHistAmt">
                    {{ $h->currency }} {{ number_format((float)$h->amount, 2, '.', ',') }}
                  </div>

                  <div class="dHistMeta">
                    <div>{{ $h->created_at->format('d/m/Y H:i:s') }}</div>
                    @if($h->reference)
                      <div class="dHistRef">{{ $h->reference }}</div>
                    @endif
                    @if($h->trade_no)
                      <div class="dHistRef">Gateway: {{ $h->trade_no }}</div>
                    @endif
                    @if($h->trade_code)
                      <div class="dHistRef">Channel: {{ $h->trade_code }}</div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

      </div>
    </section>

    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'funds', 'activeSub' => 'deposit'])

      <section class="accMain">
        <div class="accBlock" style="margin-top:0;">
          <div class="accBlock__head">
            <div class="accBlock__title">Deposit</div>
          </div>

          <div class="depDeskGrid">
            <div class="dCard dCard--desk">
              @if(session('success'))
                <div class="dFlash">{{ session('success') }}</div>
              @endif

              @if($errors->any())
                <div class="dFlash dFlash--error">
                  {{ $errors->first() }}
                </div>
              @endif

              @include('deposits._form')
            </div>

            <div class="depDeskSide">
              <div class="dNotice dNotice--desk">
                <button class="dNotice__head" type="button" data-dep-notice-toggle aria-expanded="false">
                  <span>⚠ Important Notice</span>
                  <span class="dNotice__toggle">Show</span>
                </button>

                <div class="dNotice__body" data-dep-notice-body>
                  <ul class="dNotice__list">
                    <li>Always check the latest active deposit details before making a deposit.</li>
                    <li>Cash deposits are not accepted.</li>
                    <li>Account name should match your registered name.</li>
                    <li>Do not include sensitive words in online transfer remarks.</li>
                    <li>Support is available via live chat if pending too long.</li>
                  </ul>
                </div>
              </div>

              <div class="dHistory dHistory--desk">
                <div class="dHistory__title">🕒 Deposit History (Today)</div>
                <div class="dHistory__sub">Transaction</div>

                @if($history->isEmpty())
                  <div class="dHistoryEmpty">
                    <div class="dHistoryEmpty__ico">📦</div>
                    <div class="dHistoryEmpty__title">No Data</div>
                    <div class="dHistoryEmpty__sub">No items found. Please try a different search.</div>
                  </div>
                @else
                  <div class="dHistList">
                    @foreach($history as $h)
                      <div class="dHistItem">
                        <div class="dHistTop">
                          <div class="dHistBank">
                            @if($h->method === 'bank_transfer')
                              {{ $h->bank_name ?: '-' }}
                            @elseif($h->method === 'e_wallet')
                              @if($h->provider === 'vpay')
                                E-Wallet (VPay)
                              @elseif($h->provider === 'winpay')
                                E-Wallet (WinPay)
                              @else
                                E-Wallet
                              @endif
                            @else
                              {{ ucfirst(str_replace('_', ' ', $h->method)) }}
                            @endif
                          </div>
                          <div class="dHistStatus {{ $h->status === 'pending' ? 'is-pending' : '' }}">
                            {{ $h->status === 'pending' ? 'In Progress' : ucfirst($h->status) }}
                          </div>
                        </div>

                        <div class="dHistAmt">
                          {{ $h->currency }} {{ number_format((float)$h->amount, 2, '.', ',') }}
                        </div>

                        <div class="dHistMeta">
                          <div>{{ $h->created_at->format('d/m/Y H:i:s') }}</div>
                          @if($h->reference)
                            <div class="dHistRef">{{ $h->reference }}</div>
                          @endif
                          @if($h->trade_no)
                            <div class="dHistRef">Gateway: {{ $h->trade_no }}</div>
                          @endif
                          @if($h->trade_code)
                            <div class="dHistRef">Channel: {{ $h->trade_code }}</div>
                          @endif
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          </div>

        </div>
      </section>
    </div>

  </main>
@endsection
