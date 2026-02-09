@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
    $statusText = function($s){
      return match((int)$s){
        0 => 'Pending',
        1 => 'Completed',
        2 => 'Reversed',
        3 => 'Failed',
        4 => 'Cancelled',
        default => 'Unknown',
      };
    };

    $baseQuery = [
      'type' => $type ?? '',
      'range' => $range ?? 'today',
      'from' => $from ?? null,
      'to' => $to ?? null,
    ];
  @endphp

  <main class="accPage">

    {{-- =========================
        MOBILE
        ========================= --}}
    <section class="accMobile">
      <div class="wrap">

        @include('partials.account_mobile_dashboard', [
          'currency' => $currency,
          'cash' => $cash,
          'chips' => $chips,
          'bonus' => $bonus,
          'fmt' => $fmt,
        ])

        <div class="mBlock">
          <div class="mProfile__head">
            <a class="mBack" href="{{ route('profile.index') }}">← Back</a>
            <div class="mProfile__title">Transaction History</div>
          </div>

          <form method="get" action="{{ route('history.transactions') }}">
            <label class="histLabel">Transaction Type</label>
            <select class="histSelect" name="type" onchange="this.form.submit()">
              <option value="">Select transaction type</option>
              <option value="adjustment" {{ ($type??'')==='adjustment' ? 'selected' : '' }}>Adjustment Transaction</option>
              <option value="bonus" {{ ($type??'')==='bonus' ? 'selected' : '' }}>Bonus History</option>
              <option value="deposit" {{ ($type??'')==='deposit' ? 'selected' : '' }}>Deposit Transaction</option>
              <option value="rebate" {{ ($type??'')==='rebate' ? 'selected' : '' }}>Rebate Transaction</option>
              <option value="referral_rebate" {{ ($type??'')==='referral_rebate' ? 'selected' : '' }}>Referral Rebate Transaction</option>
              <option value="transfer" {{ ($type??'')==='transfer' ? 'selected' : '' }}>Transfer History</option>
              <option value="withdrawal" {{ ($type??'')==='withdrawal' ? 'selected' : '' }}>Withdrawal Transaction</option>
            </select>

            <div class="histRange">
              <a class="histChip {{ ($range??'today')==='today' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'today'])) }}">Today</a>

              <a class="histChip {{ ($range??'')==='yesterday' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'yesterday'])) }}">Yesterday</a>

              <a class="histChip {{ ($range??'')==='past7' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'past7'])) }}">Past 7 days</a>

              <a class="histChip {{ ($range??'')==='past30' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'past30'])) }}">Past 30 days</a>

              <a class="histChip {{ ($range??'')==='this_month' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'this_month'])) }}">This Month</a>

              <a class="histChip {{ ($range??'')==='last_month' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'last_month'])) }}">Last Month</a>

              <a class="histChip {{ ($range??'')==='custom' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'custom','from'=>$from,'to'=>$to])) }}">Custom</a>
            </div>

            <div class="histCustom">
              <input class="histDate" type="date" name="from" value="{{ $from }}">
              <input class="histDate" type="date" name="to" value="{{ $to }}">
              <input type="hidden" name="range" value="custom">
              @if(($type ?? '') !== '')
                <input type="hidden" name="type" value="{{ $type }}">
              @endif
              <button class="histGo" type="submit">Go</button>
            </div>
          </form>
        </div>

        <div class="mBlock">
          <div class="histList">
            @forelse($txs as $tx)
              @php
                $isCredit = ($tx->direction === \App\Models\WalletTransaction::DIR_CREDIT);
                $amtSign = $isCredit ? '+' : '-';
              @endphp

              <div class="histCard">
                <div class="histCard__top">
                  <div class="histCard__title">{{ $tx->title ?: 'Transaction' }}</div>
                  <div class="histAmt {{ $isCredit ? 'is-pos' : 'is-neg' }}">
                    {{ $amtSign }}{{ $currency }} {{ $fmt($tx->amount) }}
                  </div>
                </div>

                <div class="histCard__meta">
                  <span>{{ optional($tx->created_at)->format('Y-m-d H:i') }}</span>
                  <span class="histBadge">{{ strtoupper($tx->wallet_type) }}</span>
                  <span class="histStatusPill histStatusPill--{{ (int)$tx->status }}">{{ $statusText($tx->status) }}</span>
                </div>

                @if($tx->reference)
                  <div class="histRef">{{ $tx->reference }}</div>
                @endif
              </div>
            @empty
              <div class="mEmpty">
                <div class="mEmpty__ico">🗂️</div>
                <div class="mEmpty__title">No Data</div>
                <div class="mEmpty__sub">No items found. Please try a different search.</div>
              </div>
            @endforelse
          </div>

          @if($txs->hasPages())
            <div class="histPager">
              @if($txs->onFirstPage())
                <span class="histPager__btn is-disabled">Prev</span>
              @else
                <a class="histPager__btn" href="{{ $txs->previousPageUrl() }}">Prev</a>
              @endif

              <span class="histPager__meta">Page {{ $txs->currentPage() }} / {{ $txs->lastPage() }}</span>

              @if($txs->hasMorePages())
                <a class="histPager__btn" href="{{ $txs->nextPageUrl() }}">Next</a>
              @else
                <span class="histPager__btn is-disabled">Next</span>
              @endif
            </div>
          @endif
        </div>

      </div>
    </section>

    {{-- =========================
        DESKTOP
        ========================= --}}
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'history', 'activeSub' => 'transactions'])

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

        <div class="histFilters">
          <form method="get" action="{{ route('history.transactions') }}">
            <div class="histFilters__row">
              <div class="histField">
                <div class="histLabel">Transaction Type *</div>
                <select class="histSelect" name="type" onchange="this.form.submit()">
                  <option value="">Select transaction type</option>
                  <option value="adjustment" {{ ($type??'')==='adjustment' ? 'selected' : '' }}>Adjustment Transaction</option>
                  <option value="bonus" {{ ($type??'')==='bonus' ? 'selected' : '' }}>Bonus History</option>
                  <option value="deposit" {{ ($type??'')==='deposit' ? 'selected' : '' }}>Deposit Transaction</option>
                  <option value="rebate" {{ ($type??'')==='rebate' ? 'selected' : '' }}>Rebate Transaction</option>
                  <option value="referral_rebate" {{ ($type??'')==='referral_rebate' ? 'selected' : '' }}>Referral Rebate Transaction</option>
                  <option value="transfer" {{ ($type??'')==='transfer' ? 'selected' : '' }}>Transfer History</option>
                  <option value="withdrawal" {{ ($type??'')==='withdrawal' ? 'selected' : '' }}>Withdrawal Transaction</option>
                </select>
              </div>
            </div>

            <div class="histRange">
              <a class="histChip {{ ($range??'today')==='today' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'today'])) }}">Today</a>
              <a class="histChip {{ ($range??'')==='yesterday' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'yesterday'])) }}">Yesterday</a>
              <a class="histChip {{ ($range??'')==='past7' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'past7'])) }}">Past 7 days</a>
              <a class="histChip {{ ($range??'')==='past30' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'past30'])) }}">Past 30 days</a>
              <a class="histChip {{ ($range??'')==='this_month' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'this_month'])) }}">This Month</a>
              <a class="histChip {{ ($range??'')==='last_month' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'last_month'])) }}">Last Month</a>
              <a class="histChip {{ ($range??'')==='custom' ? 'is-active' : '' }}"
                 href="{{ route('history.transactions', array_filter(['type'=>$type,'range'=>'custom','from'=>$from,'to'=>$to])) }}">Custom</a>
            </div>

            <div class="histCustom">
              <input class="histDate" type="date" name="from" value="{{ $from }}">
              <input class="histDate" type="date" name="to" value="{{ $to }}">
              <input type="hidden" name="range" value="custom">
              @if(($type ?? '') !== '')
                <input type="hidden" name="type" value="{{ $type }}">
              @endif
              <button class="histGo" type="submit">Go</button>
            </div>
          </form>
        </div>

        <div class="histTable">
          <div class="histHead histHead--tx">
            <div>Date</div>
            <div>Type</div>
            <div>Wallet</div>
            <div class="tRight">Amount</div>
            <div class="tRight">Status</div>
          </div>

          <div class="histBody">
            @forelse($txs as $tx)
              @php
                $isCredit = ($tx->direction === \App\Models\WalletTransaction::DIR_CREDIT);
                $amtSign = $isCredit ? '+' : '-';
              @endphp

              <div class="histRow histRow--tx">
                <div>{{ optional($tx->created_at)->format('Y-m-d H:i') }}</div>
                <div>
                  <div class="histStrong">{{ $tx->title ?: 'Transaction' }}</div>
                  @if($tx->reference)
                    <div class="histSmall">{{ $tx->reference }}</div>
                  @endif
                </div>
                <div>{{ strtoupper($tx->wallet_type) }}</div>
                <div class="tRight histAmount {{ $isCredit ? 'is-pos' : 'is-neg' }}">
                  {{ $amtSign }}{{ $currency }} {{ $fmt($tx->amount) }}
                </div>
                <div class="tRight">
                  <span class="histStatusPill histStatusPill--{{ (int)$tx->status }}">{{ $statusText($tx->status) }}</span>
                </div>
              </div>
            @empty
              <div class="histEmpty">
                <div class="histEmpty__ico">🗂️</div>
                <div class="histEmpty__title">No Data</div>
                <div class="histEmpty__sub">No items found. Please try a different search.</div>
              </div>
            @endforelse
          </div>
        </div>

        @if($txs->hasPages())
          <div class="histPager">
            @if($txs->onFirstPage())
              <span class="histPager__btn is-disabled">Prev</span>
            @else
              <a class="histPager__btn" href="{{ $txs->previousPageUrl() }}">Prev</a>
            @endif

            <span class="histPager__meta">Page {{ $txs->currentPage() }} / {{ $txs->lastPage() }}</span>

            @if($txs->hasMorePages())
              <a class="histPager__btn" href="{{ $txs->nextPageUrl() }}">Next</a>
            @else
              <span class="histPager__btn is-disabled">Next</span>
            @endif
          </div>
        @endif
      </section>
    </div>

  </main>
@endsection
