@extends('admins.layout')

@section('title', 'Wallet Transactions')

@section('body')
<style>
  .wallettx-page #tableWrap{
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

  .wallettx-page #tableWrap table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    min-width:1600px;
    table-layout:fixed;
  }

  .wallettx-page #tableWrap thead th{
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

  .wallettx-page #tableWrap th,
  .wallettx-page #tableWrap td{
    padding: 10px 10px;
    white-space:nowrap !important;
    overflow:hidden;
    text-overflow:ellipsis;
    vertical-align:middle;
    border-bottom: 1px solid var(--tbl-border);
    font-size: 13px;
    color: rgba(255,255,255,.88);
  }

  .wallettx-page #tableWrap th:not(:last-child),
  .wallettx-page #tableWrap td:not(:last-child){
    border-right: 1px solid rgba(255,255,255,.06);
  }

  .wallettx-page #tableWrap tbody tr:nth-child(odd){ background: var(--tbl-row-a); }
  .wallettx-page #tableWrap tbody tr:nth-child(even){ background: var(--tbl-row-b); }
  .wallettx-page #tableWrap tbody tr:hover{ background: var(--tbl-hover); }
  .wallettx-page #tableWrap tbody tr:last-child td{ border-bottom: 0; }

  .wallettx-page #tableWrap .clip{
    display:block;
    width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  .wallettx-page #tableWrap .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 12px;
    opacity: .95;
  }

  .wallettx-page #tableWrap td .sub{
    display:block;
    margin-top:2px;
    font-size: 12px;
    color: var(--tbl-text-dim);
    opacity: .95;
  }

  .wallettx-page #tableWrap th.col-money,
  .wallettx-page #tableWrap td.col-money{
    text-align:right;
    font-variant-numeric: tabular-nums;
  }

  .wallettx-page #tableWrap td.col-dir{ font-weight: 800; }

  .wallettx-page #tableWrap .pill{
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

  .wallettx-page #tableWrap .dot{
    width:8px; height:8px;
    border-radius:999px;
    background: rgba(255,255,255,.55);
    flex: 0 0 auto;
  }

  .wallettx-page #tableWrap .dir-credit .dot{ background:#34d399; }
  .wallettx-page #tableWrap .dir-debit  .dot{ background:#fb7185; }

  .wallettx-page #tableWrap .st-0 .dot{ background:#fbbf24; }
  .wallettx-page #tableWrap .st-1 .dot{ background:#34d399; }
  .wallettx-page #tableWrap .st-2 .dot{ background:#a1a1aa; }
  .wallettx-page #tableWrap .st-3 .dot{ background:#fb7185; }
  .wallettx-page #tableWrap .st-4 .dot{ background:#a78bfa; }

  .wallettx-page #tableWrap td.col-actions .btn{
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 10px;
  }

  .wallettx-page #tableWrap th.col-id,       .wallettx-page #tableWrap td.col-id      { width: 70px; }
  .wallettx-page #tableWrap th.col-time,     .wallettx-page #tableWrap td.col-time    { width: 170px; }
  .wallettx-page #tableWrap th.col-user,     .wallettx-page #tableWrap td.col-user    { width: 230px; }
  .wallettx-page #tableWrap th.col-wallet,   .wallettx-page #tableWrap td.col-wallet  { width: 110px; }
  .wallettx-page #tableWrap th.col-dir,      .wallettx-page #tableWrap td.col-dir     { width: 120px; }
  .wallettx-page #tableWrap th.col-money,    .wallettx-page #tableWrap td.col-money   { width: 120px; }
  .wallettx-page #tableWrap th.col-status,   .wallettx-page #tableWrap td.col-status  { width: 150px; }
  .wallettx-page #tableWrap th.col-title,    .wallettx-page #tableWrap td.col-title   { width: 220px; }
  .wallettx-page #tableWrap th.col-provider, .wallettx-page #tableWrap td.col-provider{ width: 160px; }
  .wallettx-page #tableWrap th.col-game,     .wallettx-page #tableWrap td.col-game    { width: 240px; }
  .wallettx-page #tableWrap th.col-ref,      .wallettx-page #tableWrap td.col-ref     { width: 260px; }
  .wallettx-page #tableWrap th.col-round,    .wallettx-page #tableWrap td.col-round   { width: 260px; }
  .wallettx-page #tableWrap th.col-actions,  .wallettx-page #tableWrap td.col-actions { width: 120px; }
</style>

<div class="app wallettx-page">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
      <div>
        <div style="font-size:22px; font-weight:800;">Wallet Transactions</div>
        <div style="opacity:.85; margin-top:4px;">
          Total: <strong id="totalCount">{{ $txs->total() }}</strong>
          <span style="margin-left:10px;">Credit: <strong id="sumCredit">{{ $stats['credit_sum'] ?? '0.00' }}</strong></span>
          <span style="margin-left:10px;">Debit: <strong id="sumDebit">{{ $stats['debit_sum'] ?? '0.00' }}</strong></span>
          <span style="margin-left:10px;">Net: <strong id="sumNet">{{ $stats['net_sum'] ?? '0.00' }}</strong></span>
        </div>
      </div>

      <div style="display:flex; gap:8px; align-items:flex-end;">
        <button id="btnOpenAdjust" class="btn" type="button">Admin Adjust</button>
        <a id="btnExport" class="btn" href="{{ route('admin.wallettx.export.csv') }}">Export CSV</a>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">

        <div style="flex:1; min-width:260px;">
          <label class="label">Search</label>
          <input id="q" class="input" placeholder="username / email / reference / tx_hash / round_ref / bet_id / game_code / title" />
        </div>

        <div style="width:140px;">
          <label class="label">User ID</label>
          <input id="user_id" class="input" placeholder="123" />
        </div>

        <div style="width:160px;">
          <label class="label">Wallet Type</label>
          <select id="wallet_type" class="input">
            <option value="all">All</option>
            <option value="chips">chips</option>
            <option value="main">main</option>
            <option value="bonus">bonus</option>
            <option value="promote">promote</option>
            <option value="extra">extra</option>
          </select>
        </div>

        <div style="width:160px;">
          <label class="label">Direction</label>
          <select id="direction" class="input">
            <option value="all">All</option>
            <option value="credit">credit</option>
            <option value="debit">debit</option>
          </select>
        </div>

        <div style="width:170px;">
          <label class="label">Status</label>
          <select id="status" class="input">
            <option value="all">All</option>
            <option value="0">pending</option>
            <option value="1">completed</option>
            <option value="2">reversed</option>
            <option value="3">failed</option>
            <option value="4">cancelled</option>
          </select>
        </div>

        <div style="width:160px;">
          <label class="label">Provider</label>
          <input id="provider" class="input" placeholder="PPS / AMS / ..." />
        </div>

        <div style="width:200px;">
          <label class="label">Game Code</label>
          <input id="game" class="input" placeholder="Aggr-..." />
        </div>

        <div style="width:240px;">
          <label class="label">Reference</label>
          <input id="reference" class="input" placeholder="admin_adjust:... / P_... / S_..." />
        </div>

        <div style="width:240px;">
          <label class="label">Round Ref</label>
          <input id="round_ref" class="input" placeholder="shared settle key" />
        </div>

        <div style="width:240px;">
          <label class="label">Bet ID</label>
          <input id="bet_id" class="input" placeholder="provider bet id" />
        </div>

        <div style="width:170px;">
          <label class="label">Created From</label>
          <input id="from" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:170px;">
          <label class="label">Created To</label>
          <input id="to" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:170px;">
          <label class="label">Occurred From</label>
          <input id="occurred_from" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:170px;">
          <label class="label">Occurred To</label>
          <input id="occurred_to" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:150px;">
          <label class="label">Min Amount</label>
          <input id="min_amount" class="input" placeholder="0" />
        </div>

        <div style="width:150px;">
          <label class="label">Max Amount</label>
          <input id="max_amount" class="input" placeholder="1000" />
        </div>

        <div style="display:flex; gap:10px; align-items:flex-end; padding-bottom:2px;">
          <label style="display:flex; gap:8px; align-items:center; opacity:.9; font-size:13px;">
            <input type="checkbox" id="only_admin" />
            Admin Only
          </label>

          <label style="display:flex; gap:8px; align-items:center; opacity:.9; font-size:13px;">
            <input type="checkbox" id="only_provider" />
            Provider Only
          </label>

          <label style="display:flex; gap:8px; align-items:center; opacity:.9; font-size:13px;">
            <input type="checkbox" id="only_with_meta" />
            Has Meta
          </label>
        </div>

        <div style="display:flex; align-items:flex-end;">
          <button id="btnSearch" class="btn" type="button">Search</button>
        </div>

      </div>
    </div>

    <div class="card">
      <div id="tableWrap">
        @include('admins.wallettx.partials.table', ['txs' => $txs])
      </div>

      <div id="paginationWrap" style="margin-top:12px;">
        {!! $txs->links('vendor.pagination.admin') !!}
      </div>
    </div>

    {{-- TX modal --}}
    <div id="txModalBackdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9998;"></div>

    <div id="txModal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:24px;">
      <div class="card" style="width:min(1200px, 98vw); max-height:88vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
          <div style="font-size:18px; font-weight:800;" id="txModalTitle">Transaction</div>
          <button class="btn btn-danger" type="button" id="txModalClose">Close</button>
        </div>
        <div id="txModalBody" style="margin-top:12px; opacity:.95;">Loading...</div>
      </div>
    </div>

    {{-- Admin adjust modal --}}
    <div id="adjModalBackdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9998;"></div>

    <div id="adjModal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:24px;">
      <div class="card" style="width:min(700px, 98vw); max-height:88vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
          <div style="font-size:18px; font-weight:800;">Admin Adjust</div>
          <button class="btn btn-danger" type="button" id="adjModalClose">Close</button>
        </div>

        <div style="margin-top:12px;">
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
              <label class="label">User ID</label>
              <input id="adj_user_id" class="input" placeholder="1" />
            </div>
            <div>
              <label class="label">Wallet Type</label>
              <select id="adj_wallet_type" class="input">
                <option value="chips">chips</option>
                <option value="main">main</option>
                <option value="bonus">bonus</option>
                <option value="promote">promote</option>
                <option value="extra">extra</option>
              </select>
            </div>
            <div>
              <label class="label">Direction</label>
              <select id="adj_direction" class="input">
                <option value="credit">credit</option>
                <option value="debit">debit</option>
              </select>
            </div>
            <div>
              <label class="label">Amount</label>
              <input id="adj_amount" class="input" placeholder="10.00" />
            </div>
            <div style="grid-column:1 / -1;">
              <label class="label">Title</label>
              <input id="adj_title" class="input" placeholder="Admin Adjust" />
            </div>
            <div style="grid-column:1 / -1;">
              <label class="label">Description</label>
              <input id="adj_desc" class="input" placeholder="Reason / note" />
            </div>
          </div>

          <div style="margin-top:12px; display:flex; gap:10px; justify-content:flex-end;">
            <button id="adjSubmit" class="btn" type="button">Submit Adjust</button>
          </div>

          <div id="adjMsg" style="margin-top:10px; opacity:.9;"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  const el = (id) => document.getElementById(id);

  const MODAL_TPL = @json(route('admin.wallettx.modal', ['tx' => '__TX__']));

  const filterIds = [
    'q','user_id','wallet_type','direction','status',
    'provider','game','reference','round_ref','bet_id',
    'from','to','occurred_from','occurred_to',
    'min_amount','max_amount',
    'only_admin','only_provider','only_with_meta'
  ];

  const inputs = filterIds.map(el);
  let timer = null;

  function paramsFromUI() {
    const params = new URLSearchParams();

    const q = el('q').value.trim();
    const user_id = el('user_id').value.trim();
    const wallet_type = el('wallet_type').value;
    const direction = el('direction').value;
    const status = el('status').value;
    const provider = el('provider').value.trim();
    const game = el('game').value.trim();
    const reference = el('reference').value.trim();
    const round_ref = el('round_ref').value.trim();
    const bet_id = el('bet_id').value.trim();

    const from = el('from').value.trim();
    const to = el('to').value.trim();
    const occurred_from = el('occurred_from').value.trim();
    const occurred_to = el('occurred_to').value.trim();

    const min_amount = el('min_amount').value.trim();
    const max_amount = el('max_amount').value.trim();

    const only_admin = el('only_admin').checked ? '1' : '0';
    const only_provider = el('only_provider').checked ? '1' : '0';
    const only_with_meta = el('only_with_meta').checked ? '1' : '0';

    if (q) params.set('q', q);
    if (user_id) params.set('user_id', user_id);

    if (wallet_type !== 'all') params.set('wallet_type', wallet_type);
    if (direction !== 'all') params.set('direction', direction);
    if (status !== 'all') params.set('status', status);

    if (provider) params.set('provider', provider);
    if (game) params.set('game', game);
    if (reference) params.set('reference', reference);
    if (round_ref) params.set('round_ref', round_ref);
    if (bet_id) params.set('bet_id', bet_id);

    if (from) params.set('from', from);
    if (to) params.set('to', to);
    if (occurred_from) params.set('occurred_from', occurred_from);
    if (occurred_to) params.set('occurred_to', occurred_to);

    if (min_amount) params.set('min_amount', min_amount);
    if (max_amount) params.set('max_amount', max_amount);

    if (only_admin === '1') params.set('only_admin', '1');
    if (only_provider === '1') params.set('only_provider', '1');
    if (only_with_meta === '1') params.set('only_with_meta', '1');

    return params;
  }

  function buildSearchUrl(pageUrl) {
    if (pageUrl) return pageUrl;
    const base = "{{ route('admin.wallettx.search') }}";
    const params = paramsFromUI();
    return params.toString() ? (base + "?" + params.toString()) : base;
  }

  function updateExportLink() {
    const base = "{{ route('admin.wallettx.export.csv') }}";
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
      el('sumCredit').textContent = r.data.stats.credit_sum ?? '0.00';
      el('sumDebit').textContent = r.data.stats.debit_sum ?? '0.00';
      el('sumNet').textContent = r.data.stats.net_sum ?? '0.00';
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

  function closeTxModal() {
    el('txModalBackdrop').style.display = 'none';
    el('txModal').style.display = 'none';
  }

  function buildModalUrl(txId) {
    return MODAL_TPL.replace('__TX__', String(txId));
  }

  async function openTxModal(txId) {
    el('txModalBackdrop').style.display = 'block';
    el('txModal').style.display = 'flex';
    el('txModalTitle').textContent = `TX #${txId}`;
    el('txModalBody').innerHTML = 'Loading...';

    const url = buildModalUrl(txId);
    const r = await fetchJsonSafe(url);

    if (!r.ok || !r.data || typeof r.data.html === 'undefined') {
      const snippet = (r.text || '').replace(/</g, '&lt;').slice(0, 300);
      el('txModalBody').innerHTML = `
        <div style="padding:12px;">
          Failed to load modal (HTTP ${r.status}).<br>
          <div style="opacity:.85; font-size:12px; margin-top:6px;">${r.finalUrl || url}</div>
          ${snippet ? `<pre style="margin-top:10px; padding:10px; background:#0b1220; border:1px solid #1f335c; white-space:pre-wrap; font-size:12px; opacity:.9;">${snippet}</pre>` : ''}
        </div>`;
      return;
    }

    el('txModalBody').innerHTML = r.data.html;
    bindModalActions();
  }

  function bindListActions() {
    document.querySelectorAll('[data-view-tx]').forEach(btn => {
      btn.onclick = () => openTxModal(btn.getAttribute('data-tx-id'));
    });
  }

  function openAdjModal() {
    el('adjMsg').innerHTML = '';
    el('adjModalBackdrop').style.display = 'block';
    el('adjModal').style.display = 'flex';
  }
  function closeAdjModal() {
    el('adjModalBackdrop').style.display = 'none';
    el('adjModal').style.display = 'none';
  }

  async function submitAdjust() {
    el('adjMsg').innerHTML = 'Submitting...';

    const payload = new URLSearchParams();
    payload.set('user_id', el('adj_user_id').value.trim());
    payload.set('wallet_type', el('adj_wallet_type').value);
    payload.set('direction', el('adj_direction').value);
    payload.set('amount', el('adj_amount').value.trim());
    payload.set('title', el('adj_title').value.trim());
    payload.set('description', el('adj_desc').value.trim());

    const r = await fetchJsonSafe("{{ route('admin.wallettx.adjust') }}", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': "{{ csrf_token() }}",
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body: payload.toString()
    });

    if (!r.ok || !r.data || !r.data.ok) {
      const msg = (r.data && r.data.message) ? r.data.message : `Failed (HTTP ${r.status})`;
      el('adjMsg').innerHTML = `<div style="color:#fb7185;">${msg}</div>`;
      return;
    }

    el('adjMsg').innerHTML = `<div style="color:#34d399;">OK. Created TX #${r.data.tx_id}</div>`;
    await refreshList();
  }

  function bindModalActions() {
    const btnUpdate = document.querySelector('[data-tx-update]');
    if (btnUpdate) {
      btnUpdate.onclick = async () => {
        const txId = btnUpdate.getAttribute('data-tx-id');
        const payload = new URLSearchParams();

        ['status','title','description','reference','external_id','tx_hash','provider','round_ref','bet_id','game_code'].forEach(k => {
          const node = document.querySelector('[data-tx-field="'+k+'"]');
          if (node) payload.set(k, node.value);
        });

        const url = "{{ route('admin.wallettx.update', ['tx' => '__TX__']) }}".replace('__TX__', txId);

        const r = await fetchJsonSafe(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
          },
          body: payload.toString()
        });

        const box = document.querySelector('[data-tx-msg]');
        if (box) {
          if (!r.ok || !r.data || !r.data.ok) {
            const msg = (r.data && r.data.message) ? r.data.message : `Update failed (HTTP ${r.status})`;
            box.innerHTML = `<div style="color:#fb7185;">${msg}</div>`;
          } else {
            box.innerHTML = `<div style="color:#34d399;">Updated.</div>`;
            await refreshList();
          }
        }
      };
    }

    const btnReverse = document.querySelector('[data-tx-reverse]');
    if (btnReverse) {
      btnReverse.onclick = async () => {
        const txId = btnReverse.getAttribute('data-tx-id');
        const reason = prompt('Reversal reason (optional):') || '';
        const url = "{{ route('admin.wallettx.reverse', ['tx' => '__TX__']) }}".replace('__TX__', txId);

        const payload = new URLSearchParams();
        payload.set('reason', reason);

        const r = await fetchJsonSafe(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
          },
          body: payload.toString()
        });

        const box = document.querySelector('[data-tx-msg]');
        if (box) {
          if (!r.ok || !r.data || !r.data.ok) {
            const msg = (r.data && r.data.message) ? r.data.message : `Reverse failed (HTTP ${r.status})`;
            box.innerHTML = `<div style="color:#fb7185;">${msg}</div>`;
          } else {
            box.innerHTML = `<div style="color:#34d399;">Reversed. New TX #${r.data.reversal_id}</div>`;
            await refreshList();
          }
        }
      };
    }
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

  el('txModalClose').addEventListener('click', closeTxModal);
  el('txModalBackdrop').addEventListener('click', closeTxModal);

  el('btnOpenAdjust').addEventListener('click', openAdjModal);
  el('adjModalClose').addEventListener('click', closeAdjModal);
  el('adjModalBackdrop').addEventListener('click', closeAdjModal);
  el('adjSubmit').addEventListener('click', submitAdjust);

  bindListActions();
  bindListPagination();
  updateExportLink();
})();
</script>
@endsection
