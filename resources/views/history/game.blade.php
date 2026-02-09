@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
    $provMap = ($providers ?? collect())->keyBy('code');
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
            <div class="mProfile__title">Game History</div>
          </div>

          <form method="get" action="{{ route('history.games') }}">
            <label class="histLabel">Game Provider</label>
            <select class="histSelect" name="provider" onchange="this.form.submit()">
              <option value="">Select game provider</option>
              @foreach($providers as $p)
                <option value="{{ $p->code }}" {{ $providerCode===$p->code ? 'selected' : '' }}>
                  {{ $p->name }}
                </option>
              @endforeach
            </select>

            <div class="histRange">
              <a class="histChip {{ ($range??'today')==='today' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'today'])) }}">Today</a>

              <a class="histChip {{ ($range??'')==='yesterday' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'yesterday'])) }}">Yesterday</a>

              <a class="histChip {{ ($range??'')==='past7' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'past7'])) }}">Past 7 days</a>

              <a class="histChip {{ ($range??'')==='past30' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'past30'])) }}">Past 30 days</a>

              <a class="histChip {{ ($range??'')==='this_month' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'this_month'])) }}">This Month</a>

              <a class="histChip {{ ($range??'')==='last_month' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'last_month'])) }}">Last Month</a>

              <a class="histChip {{ ($range??'')==='custom' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'custom','from'=>$from,'to'=>$to])) }}">Custom</a>
            </div>

            <div class="histCustom">
              <input class="histDate" type="date" name="from" value="{{ $from }}">
              <input class="histDate" type="date" name="to" value="{{ $to }}">
              <input type="hidden" name="range" value="custom">
              @if(($providerCode ?? '') !== '')
                <input type="hidden" name="provider" value="{{ $providerCode }}">
              @endif
              <button class="histGo" type="submit">Go</button>
            </div>
          </form>
        </div>

        <div class="mBlock">
          <div class="histList">
            @forelse($records as $r)
              @php
                $providerName = $r->provider ? ($provMap[$r->provider]->name ?? $r->provider) : '-';
                $game = $r->game_code ?: ($r->bet_id ?: '-');
              @endphp

              <div class="histCard">
                <div class="histCard__top">
                  <div class="histCard__title">{{ $providerName }}</div>
                  <div class="histAmt is-neg">{{ $currency }} {{ $fmt((float)$r->display_stake) }}</div>
                </div>

                <div class="histCard__meta">
                  <span>{{ optional($r->created_at)->format('Y-m-d H:i') }}</span>
                  <span class="histBadge">{{ strtoupper($r->wallet_type) }}</span>
                  <span class="histBadge">{{ $game }}</span>
                </div>

                <div class="histCard__meta" style="margin-top:6px;">
                  <span class="histSmall">Win/Loss:</span>
                  <span class="histAmount {{ ((float)$r->display_winloss >= 0) ? 'is-pos' : 'is-neg' }}">
                    {{ $currency }} {{ $fmt((float)$r->display_winloss) }}
                  </span>
                </div>

                @if($r->round_ref)
                  <div class="histRef">{{ $r->round_ref }}</div>
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

          @if($records->hasPages())
            <div class="histPager">
              @if($records->onFirstPage())
                <span class="histPager__btn is-disabled">Prev</span>
              @else
                <a class="histPager__btn" href="{{ $records->previousPageUrl() }}">Prev</a>
              @endif

              <span class="histPager__meta">Page {{ $records->currentPage() }} / {{ $records->lastPage() }}</span>

              @if($records->hasMorePages())
                <a class="histPager__btn" href="{{ $records->nextPageUrl() }}">Next</a>
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
      @include('partials.account_sidebar', ['active' => 'history', 'activeSub' => 'games'])

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
          <form method="get" action="{{ route('history.games') }}">
            <div class="histFilters__row">
              <div class="histField">
                <div class="histLabel">Game Provider</div>
                <select class="histSelect" name="provider" onchange="this.form.submit()">
                  <option value="">Select game provider</option>
                  @foreach($providers as $p)
                    <option value="{{ $p->code }}" {{ $providerCode===$p->code ? 'selected' : '' }}>
                      {{ $p->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="histRange">
              <a class="histChip {{ ($range??'today')==='today' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'today'])) }}">Today</a>
              <a class="histChip {{ ($range??'')==='yesterday' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'yesterday'])) }}">Yesterday</a>
              <a class="histChip {{ ($range??'')==='past7' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'past7'])) }}">Past 7 days</a>
              <a class="histChip {{ ($range??'')==='past30' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'past30'])) }}">Past 30 days</a>
              <a class="histChip {{ ($range??'')==='this_month' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'this_month'])) }}">This Month</a>
              <a class="histChip {{ ($range??'')==='last_month' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'last_month'])) }}">Last Month</a>
              <a class="histChip {{ ($range??'')==='custom' ? 'is-active' : '' }}"
                 href="{{ route('history.games', array_filter(['provider'=>$providerCode,'range'=>'custom','from'=>$from,'to'=>$to])) }}">Custom</a>
            </div>

            <div class="histCustom">
              <input class="histDate" type="date" name="from" value="{{ $from }}">
              <input class="histDate" type="date" name="to" value="{{ $to }}">
              <input type="hidden" name="range" value="custom">
              @if(($providerCode ?? '') !== '')
                <input type="hidden" name="provider" value="{{ $providerCode }}">
              @endif
              <button class="histGo" type="submit">Go</button>
            </div>
          </form>
        </div>

        <div class="histTable">
          <div class="histHead histHead--game">
            <div>Bet Date</div>
            <div>Wallet Type</div>
            <div>Game</div>
            <div class="tRight">Bet Amount</div>
            <div class="tRight">Win/Loss</div>
          </div>

          <div class="histBody">
            @forelse($records as $r)
              @php
                $game = $r->game_code ?: ($r->bet_id ?: '-');
                $wl = (float)$r->display_winloss;
              @endphp

              <div class="histRow histRow--game">
                <div>{{ optional($r->created_at)->format('Y-m-d H:i') }}</div>
                <div>{{ strtoupper($r->wallet_type) }}</div>
                <div>
                  <div class="histStrong">{{ $game }}</div>
                  <div class="histSmall">{{ $r->provider ? ($provMap[$r->provider]->name ?? $r->provider) : '-' }}</div>
                </div>
                <div class="tRight">{{ $currency }} {{ $fmt((float)$r->display_stake) }}</div>
                <div class="tRight histAmount {{ $wl >= 0 ? 'is-pos' : 'is-neg' }}">
                  {{ $currency }} {{ $fmt($wl) }}
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

        @if($records->hasPages())
          <div class="histPager">
            @if($records->onFirstPage())
              <span class="histPager__btn is-disabled">Prev</span>
            @else
              <a class="histPager__btn" href="{{ $records->previousPageUrl() }}">Prev</a>
            @endif

            <span class="histPager__meta">Page {{ $records->currentPage() }} / {{ $records->lastPage() }}</span>

            @if($records->hasMorePages())
              <a class="histPager__btn" href="{{ $records->nextPageUrl() }}">Next</a>
            @else
              <span class="histPager__btn is-disabled">Next</span>
            @endif
          </div>
        @endif
      </section>
    </div>

  </main>
@endsection
