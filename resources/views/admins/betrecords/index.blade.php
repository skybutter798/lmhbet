{{-- resources/views/admins/betrecords/index.blade.php --}}

@extends('admins.layout')

@section('title', 'Bet Records')

@section('body')
<style>
  /* ✅ Scope ALL styles to only this page/table so sidebar won't be affected */
  .betrecords-page #tableWrap{
    /* scoped variables (NOT global) */
    --tbl-border: rgba(255,255,255,.10);
    --tbl-border-strong: rgba(255,255,255,.16);
    --tbl-head-bg: rgba(255,255,255,.06);
    --tbl-row-a: rgba(255,255,255,.02);
    --tbl-row-b: rgba(255,255,255,.00);
    --tbl-hover: rgba(255,255,255,.08);
    --tbl-text-dim: rgba(255,255,255,.72);

    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    padding-bottom:6px;
    border: 1px solid var(--tbl-border);
    border-radius: 12px;
    background: rgba(0,0,0,.10);
  }

  .betrecords-page #tableWrap table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    min-width:1400px;
    table-layout:fixed; /* keep columns fixed */
  }

  /* header */
  .betrecords-page #tableWrap thead th{
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--tbl-head-bg);
    backdrop-filter: blur(6px);
    border-bottom: 1px solid var(--tbl-border-strong);
    color: rgba(255,255,255,.92);
    font-weight: 800;
    font-size: 12px;
    letter-spacing: .2px;
    text-transform: uppercase;
  }

  .betrecords-page #tableWrap th,
  .betrecords-page #tableWrap td{
    padding: 10px 10px;
    white-space:nowrap !important;
    overflow:hidden;
    text-overflow:ellipsis;
    vertical-align:middle;
    border-bottom: 1px solid var(--tbl-border);
    font-size: 13px;
    color: rgba(255,255,255,.88);
  }

  /* vertical dividers */
  .betrecords-page #tableWrap th:not(:last-child),
  .betrecords-page #tableWrap td:not(:last-child){
    border-right: 1px solid rgba(255,255,255,.06);
  }

  /* zebra rows */
  .betrecords-page #tableWrap tbody tr:nth-child(odd){ background: var(--tbl-row-a); }
  .betrecords-page #tableWrap tbody tr:nth-child(even){ background: var(--tbl-row-b); }

  /* hover */
  .betrecords-page #tableWrap tbody tr:hover{ background: var(--tbl-hover); }

  /* remove last border line */
  .betrecords-page #tableWrap tbody tr:last-child td{ border-bottom: 0; }

  /* clip helper */
  .betrecords-page #tableWrap .clip{
    display:block;
    width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  /* monospace for ids/refs */
  .betrecords-page #tableWrap .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 12px;
    opacity: .95;
  }

  /* secondary line inside cells */
  .betrecords-page #tableWrap td .sub{
    display:block;
    margin-top:2px;
    font-size: 12px;
    color: var(--tbl-text-dim);
    opacity: .95;
  }

  /* numeric align right */
  .betrecords-page #tableWrap th.col-money,
  .betrecords-page #tableWrap td.col-money{
    text-align:right;
    font-variant-numeric: tabular-nums;
  }

  /* status pill */
  .betrecords-page #tableWrap td.col-status{ font-weight: 700; }
  .betrecords-page #tableWrap .status-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 10px;
    border-radius:999px;
    border:1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.06);
    font-size:12px;
    line-height: 1.1;
    max-width: 100%;
  }
  .betrecords-page #tableWrap .dot{
    width:8px; height:8px;
    border-radius:999px;
    background: rgba(255,255,255,.55);
    flex: 0 0 auto;
  }
  .betrecords-page #tableWrap .st-open .dot{ background:#fbbf24; }
  .betrecords-page #tableWrap .st-settled .dot{ background:#34d399; }
  .betrecords-page #tableWrap .st-cancelled .dot{ background:#fb7185; }
  .betrecords-page #tableWrap .st-void .dot{ background:#a1a1aa; }

  /* actions */
  .betrecords-page #tableWrap td.col-actions .btn{
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 10px;
  }

  /* column widths */
  .betrecords-page #tableWrap th.col-id,      .betrecords-page #tableWrap td.col-id      { width: 70px; }
  .betrecords-page #tableWrap th.col-betat,   .betrecords-page #tableWrap td.col-betat   { width: 160px; }
  .betrecords-page #tableWrap th.col-user,    .betrecords-page #tableWrap td.col-user    { width: 220px; }
  .betrecords-page #tableWrap th.col-provider,.betrecords-page #tableWrap td.col-provider{ width: 220px; }
  .betrecords-page #tableWrap th.col-game,    .betrecords-page #tableWrap td.col-game    { width: 280px; }
  .betrecords-page #tableWrap th.col-betid,   .betrecords-page #tableWrap td.col-betid   { width: 240px; }
  .betrecords-page #tableWrap th.col-round,   .betrecords-page #tableWrap td.col-round   { width: 260px; }
  .betrecords-page #tableWrap th.col-cur,     .betrecords-page #tableWrap td.col-cur     { width: 80px; }
  .betrecords-page #tableWrap th.col-wallet,  .betrecords-page #tableWrap td.col-wallet  { width: 90px; }
  .betrecords-page #tableWrap th.col-money,   .betrecords-page #tableWrap td.col-money   { width: 110px; }
  .betrecords-page #tableWrap th.col-status,  .betrecords-page #tableWrap td.col-status  { width: 140px; }
  .betrecords-page #tableWrap th.col-settled, .betrecords-page #tableWrap td.col-settled { width: 160px; }
  .betrecords-page #tableWrap th.col-actions, .betrecords-page #tableWrap td.col-actions { width: 110px; }
</style>

<div class="app betrecords-page">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
      <div>
        <div style="font-size:22px; font-weight:800;">Bet Records</div>
        <div style="opacity:.85; margin-top:4px;">
          Total: <strong id="totalCount">{{ $bets->total() }}</strong>
          <span style="margin-left:10px;">Stake: <strong id="sumStake">{{ $stats->stake_sum ?? '0.00' }}</strong></span>
          <span style="margin-left:10px;">Payout: <strong id="sumPayout">{{ $stats->payout_sum ?? '0.00' }}</strong></span>
          <span style="margin-left:10px;">Profit: <strong id="sumProfit">{{ $stats->profit_sum ?? '0.00' }}</strong></span>
        </div>
      </div>

      <div style="display:flex; gap:8px; align-items:flex-end;">
        <a id="btnExport" class="btn" href="{{ route('admin.betrecords.export.csv') }}">Export CSV</a>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">

        <div style="flex:1; min-width:260px;">
          <label class="label">Search</label>
          <input id="q" class="input" placeholder="username / email / bet_id / round_ref / references / game_code / provider" />
        </div>

        <div style="width:140px;">
          <label class="label">User ID</label>
          <input id="user_id" class="input" placeholder="123" />
        </div>

        <div style="width:160px;">
          <label class="label">Provider</label>
          <input id="provider" class="input" placeholder="code or name" />
        </div>

        <div style="width:200px;">
          <label class="label">Game</label>
          <input id="game" class="input" placeholder="game_code or name" />
        </div>

        <div style="width:160px;">
          <label class="label">Status</label>
          <select id="status" class="input">
            <option value="all">All</option>
            <option value="open">open</option>
            <option value="settled">settled</option>
            <option value="cancelled">cancelled</option>
            <option value="void">void</option>
          </select>
        </div>

        <div style="width:120px;">
          <label class="label">Currency</label>
          <input id="currency" class="input" placeholder="MYR" />
        </div>

        <div style="width:140px;">
          <label class="label">Wallet</label>
          <select id="wallet_type" class="input">
            <option value="all">All</option>
            <option value="chips">chips</option>
            <option value="main">main</option>
            <option value="bonus">bonus</option>
            <option value="promote">promote</option>
            <option value="extra">extra</option>
          </select>
        </div>

        <div style="width:170px;">
          <label class="label">Bet From</label>
          <input id="from" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:170px;">
          <label class="label">Bet To</label>
          <input id="to" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:170px;">
          <label class="label">Settled From</label>
          <input id="settled_from" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:170px;">
          <label class="label">Settled To</label>
          <input id="settled_to" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:150px;">
          <label class="label">Min Stake</label>
          <input id="min_stake" class="input" placeholder="0" />
        </div>

        <div style="width:150px;">
          <label class="label">Max Stake</label>
          <input id="max_stake" class="input" placeholder="1000" />
        </div>

        <div style="width:150px;">
          <label class="label">Min Profit</label>
          <input id="min_profit" class="input" placeholder="0" />
        </div>

        <div style="width:150px;">
          <label class="label">Max Profit</label>
          <input id="max_profit" class="input" placeholder="1000" />
        </div>

        <div style="display:flex; gap:10px; align-items:flex-end; padding-bottom:2px;">
          <label style="display:flex; gap:8px; align-items:center; opacity:.9; font-size:13px;">
            <input type="checkbox" id="only_unsettled" />
            Only Unsettled
          </label>

          <label style="display:flex; gap:8px; align-items:center; opacity:.9; font-size:13px;">
            <input type="checkbox" id="only_profit" />
            Profit Only
          </label>
        </div>

        <div style="display:flex; align-items:flex-end;">
          <button id="btnSearch" class="btn" type="button">Search</button>
        </div>

      </div>
    </div>

    <div class="card">
      <div id="tableWrap">
        @include('admins.betrecords.partials.table', ['bets' => $bets])
      </div>

      <div id="paginationWrap" style="margin-top:12px;">
        {!! $bets->links('vendor.pagination.admin') !!}
      </div>
    </div>

    <div id="betModalBackdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9998;"></div>

    <div id="betModal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:24px;">
      <div class="card" style="width:min(1100px, 98vw); max-height:88vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
          <div style="font-size:18px; font-weight:800;" id="betModalTitle">Bet</div>
          <button class="btn btn-danger" type="button" id="betModalClose">Close</button>
        </div>
        <div id="betModalBody" style="margin-top:12px; opacity:.95;">Loading...</div>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  const el = (id) => document.getElementById(id);

  const MODAL_TPL = @json(route('admin.betrecords.modal', ['betRecord' => '__BET__']));

  const filterIds = [
    'q','user_id','provider','game','status','currency','wallet_type',
    'from','to','settled_from','settled_to',
    'min_stake','max_stake','min_profit','max_profit',
    'only_unsettled','only_profit'
  ];

  const inputs = filterIds.map(el);
  let timer = null;

  function paramsFromUI() {
    const params = new URLSearchParams();

    const q = el('q').value.trim();
    const user_id = el('user_id').value.trim();
    const provider = el('provider').value.trim();
    const game = el('game').value.trim();
    const status = el('status').value;
    const currency = el('currency').value.trim();
    const wallet_type = el('wallet_type').value;

    const from = el('from').value.trim();
    const to = el('to').value.trim();
    const settled_from = el('settled_from').value.trim();
    const settled_to = el('settled_to').value.trim();

    const min_stake = el('min_stake').value.trim();
    const max_stake = el('max_stake').value.trim();
    const min_profit = el('min_profit').value.trim();
    const max_profit = el('max_profit').value.trim();

    const only_unsettled = el('only_unsettled').checked ? '1' : '0';
    const only_profit = el('only_profit').checked ? '1' : '0';

    if (q) params.set('q', q);
    if (user_id) params.set('user_id', user_id);
    if (provider) params.set('provider', provider);
    if (game) params.set('game', game);
    if (status !== 'all') params.set('status', status);
    if (currency) params.set('currency', currency);
    if (wallet_type !== 'all') params.set('wallet_type', wallet_type);

    if (from) params.set('from', from);
    if (to) params.set('to', to);
    if (settled_from) params.set('settled_from', settled_from);
    if (settled_to) params.set('settled_to', settled_to);

    if (min_stake) params.set('min_stake', min_stake);
    if (max_stake) params.set('max_stake', max_stake);

    if (min_profit) params.set('min_profit', min_profit);
    if (max_profit) params.set('max_profit', max_profit);

    if (only_unsettled === '1') params.set('only_unsettled', '1');
    if (only_profit === '1') params.set('only_profit', '1');

    return params;
  }

  function buildSearchUrl(pageUrl) {
    if (pageUrl) return pageUrl;
    const base = "{{ route('admin.betrecords.search') }}";
    const params = paramsFromUI();
    return params.toString() ? (base + "?" + params.toString()) : base;
  }

  function updateExportLink() {
    const base = "{{ route('admin.betrecords.export.csv') }}";
    const params = paramsFromUI();
    el('btnExport').href = params.toString() ? (base + "?" + params.toString()) : base;
  }

  async function fetchJsonSafe(url, options) {
    const res = await fetch(url, Object.assign({
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      }
    }, options || {}));

    const contentType = (res.headers.get('content-type') || '').toLowerCase();

    if (contentType.includes('application/json')) {
      const data = await res.json().catch(() => null);
      return { ok: res.ok, status: res.status, data, finalUrl: res.url };
    }

    const text = await res.text().catch(() => '');
    return { ok: res.ok, status: res.status, data: null, text, finalUrl: res.url };
  }

  async function refreshList(pageUrl) {
    const url = buildSearchUrl(pageUrl);
    const r = await fetchJsonSafe(url);

    if (!r.ok || !r.data || typeof r.data.html === 'undefined') {
      el('tableWrap').innerHTML = `
        <div style="padding:12px;">
          Failed to load list (HTTP ${r.status}).<br>
          <div style="opacity:.8; font-size:12px;">${r.finalUrl || url}</div>
        </div>`;
      el('paginationWrap').innerHTML = '';
      return;
    }

    el('tableWrap').innerHTML = r.data.html;
    el('paginationWrap').innerHTML = r.data.pagination || '';
    if (r.data.total !== undefined) el('totalCount').textContent = r.data.total;

    if (r.data.stats) {
      el('sumStake').textContent = r.data.stats.stake_sum ?? '0.00';
      el('sumPayout').textContent = r.data.stats.payout_sum ?? '0.00';
      el('sumProfit').textContent = r.data.stats.profit_sum ?? '0.00';
    }

    bindListActions();
    bindListPagination();
    updateExportLink();
  }

  function debounceRefresh() {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => refreshList(), 350);
  }

  function bindListPagination() {
    const wrap = el('paginationWrap');
    if (!wrap) return;

    wrap.querySelectorAll('a').forEach(a => {
      a.onclick = async (e) => {
        e.preventDefault();
        const href = a.getAttribute('href');
        if (!href) return;
        await refreshList(href);
      };
    });
  }

  function closeBetModal() {
    el('betModalBackdrop').style.display = 'none';
    el('betModal').style.display = 'none';
  }

  function buildModalUrl(betId) {
    return MODAL_TPL.replace('__BET__', String(betId));
  }

  async function openBetModal(betId) {
    el('betModalBackdrop').style.display = 'block';
    el('betModal').style.display = 'flex';
    el('betModalTitle').textContent = `Bet #${betId}`;
    el('betModalBody').innerHTML = 'Loading...';

    const url = buildModalUrl(betId);
    const r = await fetchJsonSafe(url);

    if (!r.ok || !r.data || typeof r.data.html === 'undefined') {
      const snippet = (r.text || '').replace(/</g, '&lt;').slice(0, 300);
      el('betModalBody').innerHTML = `
        <div style="padding:12px;">
          Failed to load bet modal (HTTP ${r.status}).<br>
          <div style="opacity:.85; font-size:12px; margin-top:6px;">${r.finalUrl || url}</div>
          ${snippet ? `<pre style="margin-top:10px; padding:10px; background:#0b1220; border:1px solid #1f335c; white-space:pre-wrap; font-size:12px; opacity:.9;">${snippet}</pre>` : ''}
        </div>`;
      return;
    }

    el('betModalBody').innerHTML = r.data.html;
  }

  function bindListActions() {
    document.querySelectorAll('[data-view-bet]').forEach(btn => {
      btn.onclick = () => openBetModal(btn.getAttribute('data-bet-id'));
    });
  }

  el('btnSearch').addEventListener('click', () => refreshList());

  inputs.forEach(i => {
    if (!i) return;
    const isCheckbox = (i.tagName === 'INPUT' && i.type === 'checkbox');
    if (isCheckbox) {
      i.addEventListener('change', () => refreshList());
    } else {
      i.addEventListener('input', debounceRefresh);
      i.addEventListener('change', () => refreshList());
    }
  });

  el('betModalClose').addEventListener('click', closeBetModal);
  el('betModalBackdrop').addEventListener('click', closeBetModal);

  bindListActions();
  bindListPagination();
  updateExportLink();
})();
</script>
@endsection
