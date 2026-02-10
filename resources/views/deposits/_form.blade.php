<form method="post" action="{{ route('deposit.store') }}" class="dForm">
  @csrf

  {{-- local success is already shown in parent, can keep or remove this one --}}
  @if(session('success'))
    <div class="dFlash">{{ session('success') }}</div>
  @endif

  <div class="dTitle">
    Deposit Method <span class="req">*</span>
  </div>

    <div class="dMethods" data-dep-methods>
      @php $oldMethod = old('method', 'e_wallet'); @endphp
    
      <button class="dMethod {{ $oldMethod === 'e_wallet' ? 'is-active' : '' }}"
              type="button" data-method="e_wallet">
        <div class="dMethod__ico">💳</div>
        <div class="dMethod__txt">E-WALLET<br><small>(VPay)</small></div>
      </button>
    </div>

  {{-- hidden controls for JS --}}
  <input type="hidden" name="method" value="{{ $oldMethod }}" data-dep-method-input>
  <input type="hidden" name="bank_name" value="{{ old('bank_name') }}" data-dep-bank-input>
  <input type="hidden" name="promotion_id" value="{{ old('promotion_id') }}" data-dep-promo-input>

  {{-- method error (if something goes wrong) --}}
  @error('method')
    <div class="dErr">{{ $message }}</div>
  @enderror

  {{--<div class="dRowHead">
    <div class="dTitle">Deposit Bank <span class="req">*</span></div>
    @error('bank_name') <div class="dErr">{{ $message }}</div> @enderror
  </div>--}}

  <div class="dBanks" data-dep-banks data-visible-when="bank_transfer">
    @foreach($banks as $b)
      @php $isBankActive = old('bank_name') === $b; @endphp
      <button type="button" class="dBank {{ $isBankActive ? 'is-active' : '' }}" data-bank="{{ $b }}">
        <div class="dBank__name">{{ $b }}</div>
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

  <div class="dQuick" data-dep-quick>
    @foreach([10, 20, 50, 100, 500, 1000] as $q)
      <button class="dQuickBtn" type="button" data-amt="{{ $q }}">{{ $q >= 1000 ? '1k' : $q }}</button>
    @endforeach
  </div>

  {{-- PROMOTION CARDS --}}
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

  {{-- SUMMARY BOX --}}
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