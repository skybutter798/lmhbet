{{-- /home/lmh/app/resources/views/deposits/_form.blade.php --}}
<form method="post" action="{{ route('deposit.store') }}" class="dForm">
  @csrf

  @if(session('success'))
    <div class="dFlash">{{ session('success') }}</div>
  @endif

  <div class="dTitle">
    Deposit Method <span class="req">*</span>
  </div>

  @php
    $oldMethod = old('method', 'e_wallet');
    $oldProvider = old('provider', 'vpay');
    $oldWinType = old('winpay_type', '01');
    $oldVpayTradeCode = old('trade_code', '36');
  @endphp

  <div class="dMethods" data-dep-methods>
    <!--<button class="dMethod {{ $oldMethod === 'e_wallet' ? 'is-active' : '' }}"
            type="button" data-method="e_wallet">
      <div class="dMethod__ico">💳</div>
      <div class="dMethod__txt">MYR</div>
    </button>-->

    {{-- <button class="dMethod {{ $oldMethod === 'bank_transfer' ? 'is-active' : '' }}"
            type="button" data-method="bank_transfer">
      <div class="dMethod__ico">🏦</div>
      <div class="dMethod__txt">BANK TRANSFER</div>
    </button> --}}
  </div>

  {{-- Provider selector (only for e_wallet) --}}
  <div class="dMethods" data-dep-providers data-visible-when="e_wallet" style="margin-top:10px;">
    <button class="dMethod {{ $oldProvider === 'vpay' ? 'is-active' : '' }}"
            type="button" data-provider="vpay">
      <div class="dMethod__ico">💳</div>
      <div class="dMethod__txt">VPAY<br><small></small></div>
    </button>

    <button class="dMethod {{ $oldProvider === 'winpay' ? 'is-active' : '' }}"
            type="button" data-provider="winpay">
      <div class="dMethod__ico">⚡</div>
      <div class="dMethod__txt">WINPAY<br><small></small></div>
    </button>
  </div>

  {{-- hidden controls for JS --}}
  <input type="hidden" name="method" value="{{ $oldMethod }}" data-dep-method-input>
  <input type="hidden" name="provider" value="{{ $oldProvider }}" data-dep-provider-input>
  <input type="hidden" name="winpay_type" value="{{ $oldWinType }}" data-dep-winpay-type-input>
  <input type="hidden" name="bank_name" value="{{ old('bank_name') }}" data-dep-bank-input>
  <input type="hidden" name="promotion_id" value="{{ old('promotion_id') }}" data-dep-promo-input>

  {{-- NEW: VPAY trade_code --}}
  <input type="hidden" name="trade_code" value="{{ $oldVpayTradeCode }}" data-dep-trade-code-input>

  @error('method')
    <div class="dErr">{{ $message }}</div>
  @enderror

  {{-- BANK TRANSFER banks --}}
  <div class="dBanks" data-dep-banks data-visible-when="bank_transfer">
    @foreach($banks as $b)
      @php $isBankActive = old('bank_name') === $b; @endphp
      <button type="button" class="dBank {{ $isBankActive ? 'is-active' : '' }}" data-bank="{{ $b }}">
        <div class="dBank__name">{{ $b }}</div>
        <div class="dBank__tick">✓</div>
      </button>
    @endforeach
  </div>

  {{-- WinPay Type selector --}}
  <div class="dBanks"
       data-winpay-types
       data-visible-when="e_wallet"
       data-visible-provider="winpay"
       style="margin-top:10px; display:none;">
    <button type="button" class="dBank {{ $oldWinType === '01' ? 'is-active' : '' }}" data-winpay-type="01">
      <div class="dBank__name">FPX</div>
      <div class="dBank__tick">✓</div>
    </button>

    <button type="button" class="dBank {{ $oldWinType === '03' ? 'is-active' : '' }}" data-winpay-type="03">
      <div class="dBank__name">EWallet</div>
      <div class="dBank__tick">✓</div>
    </button>
  </div>

  {{-- WinPay FPX banks (type 01) --}}
  <div class="dBanks"
       data-winpay-banks="01"
       data-visible-when="e_wallet"
       data-visible-provider="winpay"
       style="display:none;">
    @foreach($winpayFpxBanks as $b)
      @php $isActive = old('bank_name') === $b; @endphp
      <button type="button" class="dBank {{ $isActive ? 'is-active' : '' }}" data-bank="{{ $b }}">
        <div class="dBank__name">{{ $b }}</div>
        <div class="dBank__tick">✓</div>
      </button>
    @endforeach
  </div>

  {{-- WinPay EWallets (type 03) --}}
  <div class="dBanks"
       data-winpay-banks="03"
       data-visible-when="e_wallet"
       data-visible-provider="winpay"
       style="display:none;">
    @foreach($winpayEwallets as $w)
      @php $isActive = old('bank_name') === $w; @endphp
      <button type="button" class="dBank {{ $isActive ? 'is-active' : '' }}" data-bank="{{ $w }}">
        <div class="dBank__name">{{ $w }}</div>
        <div class="dBank__tick">✓</div>
      </button>
    @endforeach
  </div>

  {{-- NEW: VPAY Channel selector --}}
  <div class="dBanks"
       data-vpay-trade-codes
       data-visible-when="e_wallet"
       data-visible-provider="vpay"
       style="margin-top:10px; display:none;">
    @foreach(($vpayTradeCodes ?? ['36' => 'DUITNOW']) as $code => $label)
      <button type="button"
              class="dBank {{ (string)$oldVpayTradeCode === (string)$code ? 'is-active' : '' }}"
              data-vpay-trade-code="{{ $code }}">
        <div class="dBank__name">{{ $label }}</div>
        <div class="dBank__tick">✓</div>
      </button>
    @endforeach
  </div>

  <div class="dTitle">Deposit Amount <span class="req">*</span></div>

  <div class="dAmtWrap">
    <input class="dAmt" type="number" step="0.01" min="20" max="20000"
           name="amount" value="{{ old('amount') }}"
           placeholder="MIN: 20.00 / MAX: 20,000.00" required data-dep-amount>
    <button class="dAmtClear" type="button" aria-label="Clear" data-dep-clear>×</button>
  </div>

  @error('amount') <div class="dErr">{{ $message }}</div> @enderror
  @error('bank_name') <div class="dErr">{{ $message }}</div> @enderror
  @error('trade_code') <div class="dErr">{{ $message }}</div> @enderror

  <div class="dQuick" data-dep-quick>
    @foreach([10, 20, 50, 100, 500, 1000] as $q)
      <button class="dQuickBtn" type="button" data-amt="{{ $q }}">{{ $q >= 1000 ? '1k' : $q }}</button>
    @endforeach
  </div>

  <div class="dTitle">Promotion (Optional)</div>

  <div class="depPromos" data-dep-promos>
    @foreach($promotions as $p)
      @php
        $isActive = (string)old('promotion_id') === (string)$p->id;

        $bonusLabel = $p->bonus_type === 'percent'
          ? rtrim(rtrim(number_format((float)$p->bonus_value, 2, '.', ''), '0'), '.') . '%'
          : ($currency . ' ' . number_format((float)$p->bonus_value, 2, '.', ','));

        $turnLabel = 'x' . rtrim(rtrim(number_format((float)$p->turnover_multiplier, 2, '.', ''), '0'), '.');
        $minLabel  = $p->min_amount !== null ? number_format((float)$p->min_amount, 2, '.', ',') : '-';

        $providersStr = $p->dboxProviders?->pluck('name')->implode(', ') ?? '';
        $providersCount = $p->dboxProviders?->count() ?? 0;
      @endphp

      <button
        type="button"
        class="depPromo {{ $isActive ? 'is-active' : '' }}"
        data-promo
        data-id="{{ $p->id }}"
        data-title="{{ e($p->title) }}"
        data-bonus-type="{{ $p->bonus_type }}"
        data-bonus-value="{{ (float)$p->bonus_value }}"
        data-bonus-cap="{{ $p->bonus_cap !== null ? (float)$p->bonus_cap : '' }}"
        data-min="{{ $p->min_amount !== null ? (float)$p->min_amount : '' }}"
        data-max="{{ $p->max_amount !== null ? (float)$p->max_amount : '' }}"
        data-turn="{{ (float)$p->turnover_multiplier }}"
        data-providers="{{ e($providersStr) }}"
      >
        <span class="depPromo__x" data-promo-clear aria-label="Remove">×</span>

        <div class="depPromo__title">{{ $p->title }}</div>

        <div class="depPromo__tags">
          @foreach(($p->dboxProviders ?? collect())->take(10) as $pv)
            <span class="depTag">{{ $pv->name }}</span>
          @endforeach
          @if($providersCount > 10)
            <span class="depTag depTag--more">+{{ $providersCount - 10 }}</span>
          @endif
        </div>

        <div class="depPromo__meta">
          <div class="depPromo__metaRow"><span>Bonus</span><b>{{ $bonusLabel }}</b></div>
          <div class="depPromo__metaRow"><span>Turnover</span><b>{{ $turnLabel }}</b></div>
          <div class="depPromo__metaRow"><span>Min Deposit</span><b>{{ $minLabel }}</b></div>
        </div>
      </button>
    @endforeach
  </div>

  <label class="depTc">
    <input type="checkbox" required>
    <span>T&amp;C Applies</span>
  </label>

  <div class="depSummary" data-dep-summary data-currency="{{ $currency }}">
    <div class="depSummary__head">Deposit Summary</div>

    <div class="depSummary__grid">
      <div class="depSummary__row">
        <div class="k">Amount</div>
        <div class="v" data-sum-amount>-</div>
      </div>

      <div class="depSummary__row">
        <div class="k">Promotion</div>
        <div class="v" data-sum-promo>-</div>
      </div>

      <div class="depSummary__row">
        <div class="k">Provider(s)</div>
        <div class="v" data-sum-providers>-</div>
      </div>

      <div class="depSummary__row">
        <div class="k">Turnover</div>
        <div class="v" data-sum-turn>-</div>
      </div>

      <div class="depSummary__row">
        <div class="k">Bonus</div>
        <div class="v" data-sum-bonus>-</div>
      </div>

      <div class="depSummary__row depSummary__row--req">
        <div class="k">Turnover Requirement</div>
        <div class="v v--req" data-sum-req>-</div>
      </div>
    </div>

    <div class="depSummary__note">(Formula = (Deposit + Bonus) × Turnover)</div>
    <div class="depSummary__warn" data-sum-warn style="display:none;"></div>
  </div>

  <button class="dSubmit" type="submit">Submit</button>
</form>
