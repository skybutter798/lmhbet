/* resources/js/account.js */
(function () {
  'use strict';

  // =========================
  // Small utils
  // =========================
  function numVal(v) {
    if (v === null || v === undefined) return 0;
    var s = String(v).trim();
    if (!s) return 0;
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }

  function moneyFmtDisplay(n) {
    n = Number(n || 0);
    try {
      return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } catch (e) {
      return (Math.round(n * 100) / 100).toFixed(2);
    }
  }

  function moneyFmtInput(n) {
    n = Number(n || 0);
    return (Math.round(n * 100) / 100).toFixed(2);
  }

  function setText(el, txt) {
    if (el) el.textContent = txt;
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

  // =========================
  // Modals
  // =========================
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

  // =========================
  // Deposit helpers
  // =========================
  function updateDepositSummary(form) {
    if (!form) return;

    var amtInput  = form.querySelector('[data-dep-amount]');
    var promoInput = form.querySelector('[data-dep-promo-input]');
    var promoWrap = form.querySelector('[data-dep-promos]');
    var summary   = form.querySelector('[data-dep-summary]');

    if (!amtInput || !promoWrap || !summary || !promoInput) return;

    var currency = summary.getAttribute('data-currency') || 'MYR';
    var amount = numVal(amtInput.value);

    var active = promoWrap.querySelector('.depPromo.is-active');
    if (!active || amount <= 0) {
      summary.classList.remove('is-show');
      return;
    }

    var title = active.getAttribute('data-title') || '-';
    var providers = active.getAttribute('data-providers') || '-';

    var bonusType = active.getAttribute('data-bonus-type') || 'percent';
    var bonusValue = numVal(active.getAttribute('data-bonus-value'));
    var bonusCapRaw = active.getAttribute('data-bonus-cap');
    var bonusCap = bonusCapRaw === '' ? null : numVal(bonusCapRaw);

    var minRaw = active.getAttribute('data-min');
    var maxRaw = active.getAttribute('data-max');
    var minAmt = minRaw === '' ? null : numVal(minRaw);
    var maxAmt = maxRaw === '' ? null : numVal(maxRaw);

    var turn = numVal(active.getAttribute('data-turn'));
    if (turn <= 0) turn = 1;

    var bonus = 0;
    if (bonusType === 'fixed') bonus = bonusValue;
    else bonus = (amount * bonusValue) / 100;

    if (bonusCap !== null && bonus > bonusCap) bonus = bonusCap;
    bonus = Math.round(bonus * 100) / 100;

    var req = (amount + bonus) * turn;
    req = Math.round(req * 100) / 100;

    var elAmt = summary.querySelector('[data-sum-amount]');
    var elPromo = summary.querySelector('[data-sum-promo]');
    var elProviders = summary.querySelector('[data-sum-providers]');
    var elTurn = summary.querySelector('[data-sum-turn]');
    var elBonus = summary.querySelector('[data-sum-bonus]');
    var elReq = summary.querySelector('[data-sum-req]');

    if (elAmt) elAmt.textContent = currency + ' ' + moneyFmtDisplay(amount);
    if (elPromo) elPromo.textContent = title;
    if (elProviders) elProviders.textContent = providers || '-';
    if (elTurn) elTurn.textContent = 'x' + String(turn);
    if (elBonus) elBonus.textContent = currency + ' ' + moneyFmtDisplay(bonus);
    if (elReq) elReq.textContent = currency + ' ' + moneyFmtDisplay(req);

    var warnEl = summary.querySelector('[data-sum-warn]');
    var warn = '';
    if (minAmt !== null && amount < minAmt) warn = 'Minimum deposit for this promotion is ' + currency + ' ' + moneyFmtDisplay(minAmt) + '.';
    if (!warn && maxAmt !== null && amount > maxAmt) warn = 'Maximum deposit for this promotion is ' + currency + ' ' + moneyFmtDisplay(maxAmt) + '.';

    if (warnEl) {
      if (warn) {
        warnEl.style.display = '';
        warnEl.textContent = warn;
      } else {
        warnEl.style.display = 'none';
        warnEl.textContent = '';
      }
    }

    summary.classList.add('is-show');
  }

  function setPromoActive(form, cardOrNull) {
    if (!form) return;

    var promoWrap = form.querySelector('[data-dep-promos]');
    var promoInput = form.querySelector('[data-dep-promo-input]');
    if (!promoWrap || !promoInput) return;

    promoWrap.querySelectorAll('.depPromo').forEach(function (c) {
      c.classList.remove('is-active');
    });

    if (!cardOrNull) {
      promoInput.value = '';
      updateDepositSummary(form);
      return;
    }

    cardOrNull.classList.add('is-active');
    promoInput.value = cardOrNull.getAttribute('data-id') || '';
    updateDepositSummary(form);
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
  // Internal Transfer init
  // =========================
  function walletLabel(t) {
    if (t === 'main') return 'Cash';
    if (t === 'chips') return 'Chips';
    if (t === 'bonus') return 'Bonus';
    return t;
  }

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

      if (fromBalEl) fromBalEl.textContent = currency + ' ' + moneyFmtDisplay(getBal(from));
      if (toBalEl)   toBalEl.textContent   = currency + ' ' + moneyFmtDisplay(getBal(to));

      if (maxBtn) maxBtn.setAttribute('data-max-value', String(getBal(from)));

      if (amount) {
        var cur = numVal(amount.value);
        var mx = getBal(from);
        if (cur > mx) amount.value = moneyFmtInput(mx);
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
          amount.value = moneyFmtInput(mx);
          amount.focus();
        }
      });
    }

    root.querySelectorAll('[data-it-quick-amt]').forEach(function (q) {
      q.addEventListener('click', function (e) {
        e.preventDefault();
        var v = q.getAttribute('data-it-quick-amt');
        if (amount) {
          amount.value = moneyFmtInput(numVal(v));
          amount.focus();
        }
      });
    });

    var first = root.querySelector('[data-it-option].is-active') || (opts.length ? opts[0] : null);
    setActive(first);
  }

  // =========================
  // Bonus poller (wallet page)
  // =========================
  var bonusTimer = null;

  function hasBonusRows() {
    return !!document.querySelector('[data-bonus-id] [data-bonus-prog], [data-bonus-id] [data-bonus-req], [data-bonus-id] [data-bonus-pct]');
  }

  function stopBonusPoll() {
    if (!bonusTimer) return;
    clearInterval(bonusTimer);
    bonusTimer = null;
  }

  function startBonusPoll() {
    if (bonusTimer) return;
    if (!hasBonusRows()) return;

    fetchBonus();
    bonusTimer = setInterval(fetchBonus, 2500);
  }

  async function fetchBonus() {
    if (!hasBonusRows()) return;

    try {
      var res = await fetch('/wallet/bonus/records', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        cache: 'no-store'
      });

      if (res.status === 401) return;

      var data = await res.json().catch(function () { return null; });
      if (!res.ok || !data || !data.ok) return;

      (data.records || []).forEach(function (r) {
        var row = document.querySelector('[data-bonus-id="' + r.id + '"]');
        if (!row) return;

        var progEl = row.querySelector('[data-bonus-prog]');
        var reqEl  = row.querySelector('[data-bonus-req]');
        var pctEl  = row.querySelector('[data-bonus-pct]');
        var barEl  = row.querySelector('[data-bonus-bar]');

        var cur = r.currency || '';
        setText(progEl, cur + ' ' + moneyFmtDisplay(Number(r.progress || 0)));
        setText(reqEl,  cur + ' ' + moneyFmtDisplay(Number(r.required || 0)));
        setText(pctEl,  Math.round(Number(r.pct || 0)) + '%');

        if (barEl) barEl.style.width = String(r.pct || 0) + '%';
      });
    } catch (e) {}
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) stopBonusPoll();
    else startBonusPoll();
  });

  window.addEventListener('focus', function () {
    fetchBonus();
  });

  // =========================
  // Global click handler (delegation)
  // =========================
  document.addEventListener('click', function (e) {
    // open profile modal
    var opener = e.target.closest('[data-open-prof-modal]');
    if (opener) {
      e.preventDefault();
      openModal(opener.getAttribute('data-open-prof-modal'));
      return;
    }

    // open kyc modal
    var kycBtn = e.target.closest('[data-open-kyc-modal]');
    if (kycBtn) {
      e.preventDefault();
      openModal('kyc');
      return;
    }

    // open withdraw add account modal
    var addAcc = e.target.closest('[data-open-withdraw-add-account]');
    if (addAcc) {
      e.preventDefault();
      openModal('withdrawAddAccount');
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

    // mobile dashboard collapse
    var mdBtn = e.target.closest('[data-mdash-toggle]');
    if (mdBtn) {
      var card = mdBtn.closest('[data-mdash]');
      if (!card) return;

      e.preventDefault();

      var isCollapsed = card.classList.toggle('is-collapsed');
      mdBtn.setAttribute('aria-expanded', String(!isCollapsed));

      var txt = mdBtn.querySelector('.mDashToggle__txt');
      if (txt) txt.textContent = isCollapsed ? 'Show shortcuts' : 'Hide shortcuts';
      return;
    }

    // withdraw notice toggle
    var nt = e.target.closest('[data-withdraw-notice-toggle]');
    if (nt) {
      e.preventDefault();
      var body = document.querySelector('[data-withdraw-notice-body]');
      if (!body) return;

      var isOpen = body.classList.toggle('is-open');
      nt.textContent = isOpen ? 'Hide' : 'Show';
      return;
    }

    // withdraw quick amount
    var qbtn = e.target.closest('[data-withdraw-quick] [data-amt]');
    if (qbtn) {
      e.preventDefault();
      var amt = qbtn.getAttribute('data-amt');
      var inputAmt = document.querySelector('[data-withdraw-amount]');
      if (!inputAmt || inputAmt.disabled) return;

      inputAmt.value = String(amt);
      inputAmt.focus();
      return;
    }

    // =========================
    // DEPOSIT (scoped per form)
    // =========================

    // deposit: method select
    var mBtn = e.target.closest('[data-dep-methods] [data-method]');
    if (mBtn) {
      e.preventDefault();

      var form = mBtn.closest('form');
      var wrap = mBtn.closest('[data-dep-methods]');
      if (!wrap) return;

      wrap.querySelectorAll('[data-method]').forEach(function (b) {
        b.classList.remove('is-active');
      });
      mBtn.classList.add('is-active');

      var method = mBtn.getAttribute('data-method');
      if (form) {
        var inputM = form.querySelector('[data-dep-method-input]');
        if (inputM) inputM.value = method;

        form.querySelectorAll('[data-visible-when]').forEach(function (el) {
          var need = el.getAttribute('data-visible-when');
          el.style.display = (need === method) ? '' : 'none';
        });
      }

      return;
    }

    // deposit: bank select
    var bBtn = e.target.closest('[data-dep-banks] [data-bank]');
    if (bBtn) {
      e.preventDefault();

      var form2 = bBtn.closest('form');
      var banks = bBtn.closest('[data-dep-banks]');
      if (!form2 || !banks) return;

      banks.querySelectorAll('[data-bank]').forEach(function (b) {
        b.classList.remove('is-active');
      });
      bBtn.classList.add('is-active');

      var bank = bBtn.getAttribute('data-bank');
      var inputB = form2.querySelector('[data-dep-bank-input]');
      if (inputB) inputB.value = bank;

      return;
    }

    // deposit: quick amount
    var qBtn = e.target.closest('[data-dep-quick] [data-amt]');
    if (qBtn) {
      e.preventDefault();

      var form3 = qBtn.closest('form');
      if (!form3) return;

      var amt2 = qBtn.getAttribute('data-amt');
      var inputA = form3.querySelector('[data-dep-amount]');
      if (!inputA) return;

      inputA.value = String(amt2);
      inputA.focus();
      inputA.dispatchEvent(new Event('input', { bubbles: true }));
      return;
    }

    // deposit: clear amount
    var cBtn = e.target.closest('[data-dep-clear]');
    if (cBtn) {
      e.preventDefault();

      var form4 = cBtn.closest('form');
      if (!form4) return;

      var inputC = form4.querySelector('[data-dep-amount]');
      if (!inputC) return;

      inputC.value = '';
      inputC.focus();
      inputC.dispatchEvent(new Event('input', { bubbles: true }));
      return;
    }

    // deposit: notice toggle
    var nBtn = e.target.closest('[data-dep-notice-toggle]');
    if (nBtn) {
      e.preventDefault();

      var notice = nBtn.closest('.dNotice');
      if (!notice) return;

      var body2 = notice.querySelector('[data-dep-notice-body]');
      if (!body2) return;

      var isOpen2 = body2.classList.toggle('is-open');
      nBtn.setAttribute('aria-expanded', String(isOpen2));

      var t = nBtn.querySelector('.dNotice__toggle');
      if (t) t.textContent = isOpen2 ? 'Hide' : 'Show';
      return;
    }

    // deposit: promotion select / unselect
    var promoCard = e.target.closest('[data-dep-promos] [data-promo]');
    if (promoCard) {
      e.preventDefault();

      var form5 = promoCard.closest('form');
      if (!form5) return;

      if (e.target.closest('[data-promo-clear]')) {
        setPromoActive(form5, null);
        return;
      }

      if (promoCard.classList.contains('is-active')) {
        setPromoActive(form5, null);
      } else {
        setPromoActive(form5, promoCard);
      }
      return;
    }
  });

  // =========================
  // DOM ready init
  // =========================
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.dForm').forEach(function (form) {
      var methodInput = form.querySelector('[data-dep-method-input]');
      if (methodInput) {
        var method = methodInput.value || 'bank_transfer';
        form.querySelectorAll('[data-visible-when]').forEach(function (el) {
          var need = el.getAttribute('data-visible-when');
          el.style.display = (need === method) ? '' : 'none';
        });
      }

      var amtInput = form.querySelector('[data-dep-amount]');
      if (amtInput) {
        amtInput.addEventListener('input', function () {
          updateDepositSummary(form);
        });
      }

      updateDepositSummary(form);
    });

    document.querySelectorAll('[data-it-root]').forEach(initInternalTransfer);
    document.querySelectorAll('.accTabs[data-tabs]').forEach(initTabs);

    if (window.__OPEN_PROFILE_MODAL__ === 'email') openModal('email');
    if (window.__OPEN_PROFILE_MODAL__ === 'phone') openModal('phone');
    if (window.__OPEN_PROFILE_MODAL__ === 'kyc') openModal('kyc');
    if (window.__OPEN_PROFILE_MODAL__ === 'walletTransfer') openModal('walletTransfer');

    startBonusPoll();
  });
})();