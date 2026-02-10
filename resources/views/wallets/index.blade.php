{{-- /home/lmh/app/resources/views/wallets/index.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  <style>
    /* needed for tab panels */
    .is-hidden{ display:none !important; }
  </style>

  @php
    $fmt = fn($v) => number_format((float)$v, 2, '.', ',');
    $total = (float)$cash + (float)$chips + (float)$bonus;
  @endphp

  {{-- ✅ Auto-open modal after submit (success or validation error) --}}
  @if(session('success') || $errors->any())
    <script>window.__OPEN_PROFILE_MODAL__ = 'walletTransfer';</script>
  @endif

  <main class="accPage">

    {{-- =========================
        MOBILE WALLET DASHBOARD
        ========================= --}}
    <section class="accMobile">
      <div class="wrap">

        {{-- Wallet summary card --}}
        <div class="mWallet">
          <div class="mWallet__top">
            <div class="mWallet__title">Wallet ({{ $currency }})</div>
          </div>

          <div class="mWallet__box">
            <div class="mWallet__row">
              <span class="mWallet__label">Total Assets</span>
              <span class="mWallet__val">{{ $fmt($total) }}</span>
            </div>
            <div class="mWallet__row">
              <span class="mWallet__label">Cash</span>
              <span class="mWallet__val">{{ $fmt($cash) }}</span>
            </div>
            <div class="mWallet__row">
              <span class="mWallet__label">Chips</span>
              <span class="mWallet__val">{{ $fmt($chips) }}</span>
            </div>
            <div class="mWallet__row">
              <span class="mWallet__label">Bonus</span>
              <span class="mWallet__val">{{ $fmt($bonus) }}</span>
              <span class="mWallet__badge" title="Bonus">↗</span>
            </div>
          </div>
        </div>

        {{-- Big action tiles --}}
        <div class="mTiles">
          <a class="mTile" href="#" data-open-prof-modal="walletTransfer" title="Transfer">
            <span class="mTile__ico">⇄</span>
            <span class="mTile__txt">Transfer</span>
          </a>

          <a class="mTile" href="{{ route('withdraw.index') }}" title="Withdrawal">
            <span class="mTile__ico">↗</span>
            <span class="mTile__txt">Withdrawal</span>
          </a>

          <a class="mTile" href="{{ route('deposit.index') }}" title="Deposit">
            <span class="mTile__ico">＋</span>
            <span class="mTile__txt">Deposit</span>
          </a>
        </div>

        {{-- Bonus Record (mobile) --}}
        <div class="mBlock" id="mBonus">
          <div class="mBlock__head">
            <div class="mBlock__title">Bonus Record</div>
        
            <div class="accTabs" data-tabs="bonus-mobile" role="tablist" aria-label="Bonus Record Tabs (Mobile)">
              <button class="accTab is-active" type="button" data-tab="progress" role="tab" aria-selected="true">
                In Progress
              </button>
              <button class="accTab" type="button" data-tab="done" role="tab" aria-selected="false">
                Done
              </button>
            </div>
          </div>
        
          <div class="mTable">
        
            @php
              $inProgressM = ($bonusRecords ?? collect())->filter(fn($r) => ($r->bonus_status ?? '') === 'in_progress');
              $doneM       = ($bonusRecords ?? collect())->filter(fn($r) => ($r->bonus_status ?? '') === 'done');
            @endphp
        
            {{-- PANEL: IN PROGRESS --}}
            <div data-panel="progress">
              <div class="mTable__row mTable__row--head">
                <div>Bonus Name</div>
                <div class="tRight">Balance</div>
              </div>
        
              @if($inProgressM->isEmpty())
                <div class="mEmpty">
                  <div class="mEmpty__ico">📄</div>
                  <div class="mEmpty__title">No Data</div>
                  <div class="mEmpty__sub">No items found.</div>
                </div>
              @else
                <div class="histList">
                  @foreach($inProgressM as $r)
                    <div class="histCard">
                      <div class="histCard__top">
                        <div class="histCard__title">{{ $r->promotion?->title ?? 'Promotion' }}</div>
                        <div class="histAmt">{{ $currency }} {{ number_format((float)($r->bonus_amount ?? 0), 2, '.', ',') }}</div>
                      </div>
                      <div class="histCard__meta">
                        <span class="histBadge">Ref: {{ $r->reference }}</span>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
        
            {{-- PANEL: DONE --}}
            <div class="is-hidden" data-panel="done">
              <div class="mTable__row mTable__row--head">
                <div>Bonus Name</div>
                <div class="tRight">Balance</div>
              </div>
        
              @if($doneM->isEmpty())
                <div class="mEmpty">
                  <div class="mEmpty__ico">✅</div>
                  <div class="mEmpty__title">No Data</div>
                  <div class="mEmpty__sub">No completed items found.</div>
                </div>
              @else
                <div class="histList">
                  @foreach($doneM as $r)
                    <div class="histCard">
                      <div class="histCard__top">
                        <div class="histCard__title">{{ $r->promotion?->title ?? 'Promotion' }}</div>
                        <div class="histAmt">{{ $currency }} {{ number_format((float)($r->bonus_amount ?? 0), 2, '.', ',') }}</div>
                      </div>
                      <div class="histCard__meta">
                        <span class="histBadge">Done</span>
                        <span class="histBadge">Ref: {{ $r->reference }}</span>
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

    {{-- =========================
        DESKTOP (SIDEBAR LEFT)
        ========================= --}}
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'funds', 'activeSub' => 'wallet'])

      <section class="accMain">
        <div class="accTop">
          <div class="accTotal">
            <div class="accTotal__badge">👑</div>
            <div class="accTotal__meta">
              <div class="accTotal__label">Total Assets ({{ $currency }})</div>
              <div class="accTotal__value">{{ $fmt($total) }}</div>
            </div>
          </div>

          <div class="accMini">
            <div class="miniBox">
              <div class="miniBox__name"><span class="dot dot--cash"></span> Cash</div>
              <div class="miniBox__val">{{ $fmt($cash) }}</div>
            </div>
            <div class="miniBox">
              <div class="miniBox__name"><span class="dot dot--chips"></span> Chips</div>
              <div class="miniBox__val">{{ $fmt($chips) }}</div>
            </div>
            <div class="miniBox">
              <div class="miniBox__name"><span class="dot dot--bonus"></span> Bonus</div>
              <div class="miniBox__val">{{ $fmt($bonus) }}</div>
            </div>
          </div>
        </div>

        <div class="accActions">
          <a class="accBtn" href="#" data-open-prof-modal="walletTransfer"><span class="accBtn__ico">⇄</span> Transfer</a>
          <a class="accBtn" href="{{ route('withdraw.index') }}"><span class="accBtn__ico">↗</span> Withdrawal</a>
          <a class="accBtn" href="{{ route('deposit.index') }}"><span class="accBtn__ico">＋</span> Deposit</a>
        </div>

        {{-- Bonus Record (desktop) --}}
        <div class="accBlock">
          <div class="accBlock__head">
            <div class="accBlock__title">Bonus Record</div>

            <div class="accTabs" data-tabs="bonus-desktop" role="tablist" aria-label="Bonus Record Tabs (Desktop)">
              <button class="accTab is-active" type="button" data-tab="progress" role="tab" aria-selected="true">
                In Progress
              </button>
              <button class="accTab" type="button" data-tab="done" role="tab" aria-selected="false">
                Done
              </button>
            </div>
          </div>

          <div class="accTable">
            <div class="accTable__head">
              <div>Bonus</div>
              <div>Turnover Progress</div>
              <div class="tRight">Balance</div>
            </div>

            @php
              $inProgress = ($bonusRecords ?? collect())->filter(fn($r) => ($r->bonus_status ?? '') === 'in_progress');
              $done       = ($bonusRecords ?? collect())->filter(fn($r) => ($r->bonus_status ?? '') === 'done');
            @endphp

            {{-- PANEL: IN PROGRESS --}}
            <div class="accTable__body" data-panel="progress">
              @if($inProgress->isEmpty())
                <div class="accEmpty">
                  <div class="accEmpty__ico">📄</div>
                  <div class="accEmpty__title">No Data</div>
                  <div class="accEmpty__sub">No items found.</div>
                </div>
              @else
                @foreach($inProgress as $r)
                  @php
                    $name = $r->promotion?->title ?? 'Promotion';
                    $req = (float)($r->turnover_required ?? 0);
                    $prog = (float)($r->turnover_progress ?? 0);
                    $pct = $req > 0 ? min(100, max(0, ($prog / $req) * 100)) : 0;
                  @endphp

                  <div class="accBonusRow">
                    <div class="accBonusLeft">
                      <div class="accBonusTitle">{{ $name }}</div>
                      <div class="accBonusSub">
                        <span class="accBonusRef">Ref: {{ $r->reference }}</span>
                      </div>
                    </div>

                    <div class="accBonusMid">
                      <div class="accBonusTopLine">
                            <div class="accBonusNums">
                            <span data-bonus-prog>{{ $currency }} {{ number_format($prog, 2, '.', ',') }}</span>
                            <span class="accBonusSlash">/</span>
                            <span data-bonus-req>{{ $currency }} {{ number_format($req, 2, '.', ',') }}</span>
                          </div>
                          <div class="accBonusPct" data-bonus-pct>{{ number_format($pct, 0) }}%</div>
                        
                          <div class="accBonusBar" role="progressbar">
                            <div class="accBonusBarFill" data-bonus-bar style="width: {{ $pct }}%"></div>
                          </div>
                      </div>

                    </div>

                    <div class="accBonusRight">
                      <div class="accBonusBal">
                        {{ $currency }} {{ number_format((float)($r->bonus_amount ?? 0), 2, '.', ',') }}
                      </div>
                      <div class="accBonusLabel">Bonus</div>
                    </div>
                  </div>
                @endforeach
              @endif
            </div>

            {{-- PANEL: DONE --}}
            <div class="accTable__body is-hidden" data-panel="done">
              @if($done->isEmpty())
                <div class="accEmpty">
                  <div class="accEmpty__ico">✅</div>
                  <div class="accEmpty__title">No Data</div>
                  <div class="accEmpty__sub">No completed items found.</div>
                </div>
              @else
                @foreach($done as $r)
                  @php
                    $name = $r->promotion?->title ?? 'Promotion';
                  @endphp

                  <div class="accBonusRow">
                    <div class="accBonusLeft">
                      <div class="accBonusTitle">{{ $name }}</div>
                      <div class="accBonusSub">
                        <span class="accBonusRef">Ref: {{ $r->reference }}</span>
                      </div>
                    </div>

                    <div class="accBonusMid">
                      <div class="accBonusTopLine">
                        <div class="accBonusNums"><span>Completed</span></div>
                        <div class="accBonusPct">100%</div>
                      </div>

                      <div class="accBonusBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100">
                        <div class="accBonusBarFill" style="width: 100%"></div>
                      </div>
                    </div>

                    <div class="accBonusRight">
                      <div class="accBonusBal">
                        {{ $currency }} {{ number_format((float)($r->bonus_amount ?? 0), 2, '.', ',') }}
                      </div>
                      <div class="accBonusLabel">Bonus</div>
                    </div>
                  </div>
                @endforeach
              @endif
            </div>

          </div>
        </div>

      </section>
    </div>

    {{-- =========================
        INTERNAL TRANSFER MODAL
        ========================= --}}
    <div class="pModal" id="pModalWalletTransfer" aria-hidden="true">
      <div class="pModal__backdrop" data-close-prof-modal></div>

      <div class="pModal__panel" role="dialog" aria-modal="true" aria-labelledby="walletTransferTitle"
           data-it-root
           data-b-main="{{ (float)$cash }}"
           data-b-chips="{{ (float)$chips }}"
           data-b-bonus="{{ (float)$bonus }}"
           data-currency="{{ $currency }}">

        <button class="pModal__close" type="button" data-close-prof-modal aria-label="Close">×</button>

        <div class="pModal__title" id="walletTransferTitle">Internal Transfer</div>

        @if(session('success'))
          <div class="wdOk">{{ session('success') }}</div>
        @endif

        @if($errors->any())
          <div class="wdErr">
            <ul style="margin:0; padding-left:18px;">
              @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form class="tfForm" method="POST" action="{{ route('wallet.transfer.internal') }}" style="max-width: 100%;">
          @csrf

          <div class="tfTabs" style="margin-top:0; flex-wrap: wrap;">
            <button class="tfTab" type="button" data-it-option data-from="chips" data-to="main">Chips → Cash</button>
          </div>

          <input type="hidden" name="from" value="main" data-it-from>
          <input type="hidden" name="to" value="chips" data-it-to>

          <div class="wdHint" style="margin-top:10px">
            From <b data-it-from-name>Cash</b>
            (<span data-it-from-bal>{{ $currency }} {{ number_format((float)$cash, 2, '.', ',') }}</span>)
            → To <b data-it-to-name>Chips</b>
            (<span data-it-to-bal>{{ $currency }} {{ number_format((float)$chips, 2, '.', ',') }}</span>)
          </div>

          <label class="tfLabel">Amount</label>
          <input class="tfInput" name="amount" data-it-amount inputmode="decimal" placeholder="0.00" required>

          <div class="tfQuick">
            <button class="tfQuickBtn" type="button" data-it-max data-max-value="{{ (float)$cash }}">Max</button>
            <button class="tfQuickBtn" type="button" data-it-quick-amt="10">10</button>
            <button class="tfQuickBtn" type="button" data-it-quick-amt="50">50</button>
            <button class="tfQuickBtn" type="button" data-it-quick-amt="100">100</button>
            <button class="tfQuickBtn" type="button" data-it-quick-amt="200">200</button>
          </div>

          <button class="tfSubmit" type="submit">Transfer Now</button>
        </form>
      </div>
    </div>

  </main>

  {{-- ✅ ONE JS ONLY (modal + tabs + internal transfer) --}}
  <script>
  (function () {
    function numVal(v) {
      if (v === null || v === undefined) return 0;
      var s = String(v).trim();
      if (!s) return 0;
      var n = parseFloat(s);
      return isNaN(n) ? 0 : n;
    }

    function moneyFmt(n) {
      n = Number(n || 0);
      try {
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      } catch (e) {
        return (Math.round(n * 100) / 100).toFixed(2);
      }
    }

    function copyText(text) {
      if (!text) return;

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).catch(function () {});
        return;
      }

      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      ta.style.top = '-9999px';
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(ta);
    }

    function modalEl(which) {
      if (which === 'email') return document.getElementById('pModalEmail');
      if (which === 'phone') return document.getElementById('pModalPhone');
      if (which === 'kyc') return document.getElementById('pModalKyc');
      if (which === 'withdrawAddAccount') return document.getElementById('pModalWithdrawAddAccount');
      if (which === 'walletTransfer') return document.getElementById('pModalWalletTransfer');
      return null;
    }

    function openModal(which) {
      var el = modalEl(which);
      if (!el) return;
      el.classList.add('is-open');
      el.setAttribute('aria-hidden', 'false');
    }

    function closeAll() {
      document.querySelectorAll('.pModal').forEach(function (m) {
        m.classList.remove('is-open');
        m.setAttribute('aria-hidden', 'true');
      });
    }

    function walletLabel(t) {
      if (t === 'main') return 'Cash';
      if (t === 'chips') return 'Chips';
      if (t === 'bonus') return 'Bonus';
      return t;
    }

    // =========================
    // Internal Transfer init
    // =========================
    function initInternalTransfer(root) {
      if (!root) return;

      var balMain  = numVal(root.getAttribute('data-b-main'));
      var balChips = numVal(root.getAttribute('data-b-chips'));
      var balBonus = numVal(root.getAttribute('data-b-bonus'));
      var currency = root.getAttribute('data-currency') || '';

      function getBal(t) {
        if (t === 'main') return balMain;
        if (t === 'chips') return balChips;
        if (t === 'bonus') return balBonus;
        return 0;
      }

      var opts = root.querySelectorAll('[data-it-option]');
      var inFrom = root.querySelector('[data-it-from]');
      var inTo = root.querySelector('[data-it-to]');
      var amount = root.querySelector('[data-it-amount]');
      var maxBtn = root.querySelector('[data-it-max]');

      var fromNameEl = root.querySelector('[data-it-from-name]');
      var toNameEl   = root.querySelector('[data-it-to-name]');
      var fromBalEl  = root.querySelector('[data-it-from-bal]');
      var toBalEl    = root.querySelector('[data-it-to-bal]');

      function setActive(btn) {
        if (!btn) return;

        opts.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');

        var from = btn.getAttribute('data-from');
        var to   = btn.getAttribute('data-to');

        if (inFrom) inFrom.value = from;
        if (inTo) inTo.value = to;

        if (fromNameEl) fromNameEl.textContent = walletLabel(from);
        if (toNameEl) toNameEl.textContent = walletLabel(to);

        if (fromBalEl) fromBalEl.textContent = currency + ' ' + moneyFmt(getBal(from));
        if (toBalEl)   toBalEl.textContent   = currency + ' ' + moneyFmt(getBal(to));

        if (maxBtn) maxBtn.setAttribute('data-max-value', String(getBal(from)));

        if (amount) {
          var cur = numVal(amount.value);
          var mx = getBal(from);
          if (cur > mx) amount.value = moneyFmt(mx);
        }
      }

      opts.forEach(function (btn) {
        btn.addEventListener('click', function () {
          setActive(btn);
        });
      });

      if (maxBtn) {
        maxBtn.addEventListener('click', function (e) {
          e.preventDefault();
          var mx = numVal(maxBtn.getAttribute('data-max-value'));
          if (amount) {
            amount.value = moneyFmt(mx);
            amount.focus();
          }
        });
      }

      root.querySelectorAll('[data-it-quick-amt]').forEach(function (q) {
        q.addEventListener('click', function (e) {
          e.preventDefault();
          var v = q.getAttribute('data-it-quick-amt');
          if (amount) {
            amount.value = String(v);
            amount.focus();
          }
        });
      });

      var first = root.querySelector('[data-it-option].is-active') || (opts.length ? opts[0] : null);
      setActive(first);
    }

    // =========================
    // Tabs (mobile + desktop)
    // =========================
    function initTabs(container) {
      if (!container) return;

      var tabs = Array.prototype.slice.call(container.querySelectorAll('.accTab[data-tab]'));
      if (!tabs.length) return;

      var scope =
        container.closest('.accBlock') ||
        container.closest('.mBlock') ||
        container.parentElement;

      function setTab(name) {
        tabs.forEach(function (t) {
          var active = (t.getAttribute('data-tab') === name);
          t.classList.toggle('is-active', active);
          t.setAttribute('aria-selected', String(active));
        });

        if (!scope) return;
        scope.querySelectorAll('[data-panel]').forEach(function (p) {
          p.classList.toggle('is-hidden', p.getAttribute('data-panel') !== name);
        });
      }

      tabs.forEach(function (t) {
        t.addEventListener('click', function (e) {
          e.preventDefault();
          setTab(t.getAttribute('data-tab'));
        });
      });

      var firstActive = container.querySelector('.accTab.is-active[data-tab]');
      setTab(firstActive ? firstActive.getAttribute('data-tab') : tabs[0].getAttribute('data-tab'));
    }

    // =========================
    // Global click handler
    // =========================
    document.addEventListener('click', function (e) {
      // open modal
      var opener = e.target.closest('[data-open-prof-modal]');
      if (opener) {
        e.preventDefault();
        openModal(opener.getAttribute('data-open-prof-modal'));
        return;
      }

      // close modal
      if (e.target.closest('[data-close-prof-modal]')) {
        e.preventDefault();
        closeAll();
        return;
      }

      // copy referral
      var btn = e.target.closest('[data-copy-ref]');
      if (btn) {
        e.preventDefault();
        var input = document.querySelector('.inviteCode__input');
        if (!input) return;

        copyText(input.value);
        btn.textContent = 'Copied';
        setTimeout(function () { btn.textContent = 'Copy'; }, 900);
        return;
      }
    });

    // =========================
    // DOM ready init
    // =========================
    document.addEventListener('DOMContentLoaded', function () {
      // init internal transfer
      document.querySelectorAll('[data-it-root]').forEach(initInternalTransfer);

      // init tabs
      document.querySelectorAll('.accTabs[data-tabs]').forEach(initTabs);

      // open modal after validation/success
      if (window.__OPEN_PROFILE_MODAL__ === 'email') openModal('email');
      if (window.__OPEN_PROFILE_MODAL__ === 'phone') openModal('phone');
      if (window.__OPEN_PROFILE_MODAL__ === 'kyc') openModal('kyc');
      if (window.__OPEN_PROFILE_MODAL__ === 'walletTransfer') openModal('walletTransfer');
    });
  })();
  </script>
@endsection