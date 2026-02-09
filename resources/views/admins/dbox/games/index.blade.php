@extends('admins.layout')

@section('title', 'DBOX Games')

@section('body')
<style>
  .dbox-games-page #tableWrap{
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

  .dbox-games-page #tableWrap table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    min-width:1500px;
    table-layout:fixed;
  }

  .dbox-games-page #tableWrap thead th{
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

  .dbox-games-page #tableWrap th,
  .dbox-games-page #tableWrap td{
    padding: 10px 10px;
    white-space:nowrap !important;
    overflow:hidden;
    text-overflow:ellipsis;
    vertical-align:middle;
    border-bottom: 1px solid var(--tbl-border);
    font-size: 13px;
    color: rgba(255,255,255,.88);
  }

  .dbox-games-page #tableWrap th:not(:last-child),
  .dbox-games-page #tableWrap td:not(:last-child){
    border-right: 1px solid rgba(255,255,255,.06);
  }

  .dbox-games-page #tableWrap tbody tr:nth-child(odd){ background: rgba(255,255,255,.02); }
  .dbox-games-page #tableWrap tbody tr:nth-child(even){ background: rgba(255,255,255,.00); }
  .dbox-games-page #tableWrap tbody tr:hover{ background: rgba(255,255,255,.08); }
  .dbox-games-page #tableWrap tbody tr:last-child td{ border-bottom: 0; }

  .dbox-games-page #tableWrap .clip{ display:block; width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .dbox-games-page #tableWrap .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 12px;
    opacity: .95;
  }
  .dbox-games-page #tableWrap td .sub{ display:block; margin-top:2px; font-size:12px; color: rgba(255,255,255,.72); opacity:.95; }

  .dbox-games-page #tableWrap .pill{
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 10px; border-radius:999px;
    border:1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.06);
    font-size:12px; line-height:1.1; max-width:100%;
  }
  .dbox-games-page #tableWrap .dot{ width:8px; height:8px; border-radius:999px; background: rgba(255,255,255,.55); }
  .dbox-games-page #tableWrap .on  .dot{ background:#34d399; }
  .dbox-games-page #tableWrap .off .dot{ background:#fb7185; }
  .dbox-games-page #tableWrap .hot .dot{ background:#fbbf24; }

  .dbox-games-page #tableWrap input.inline-num{ width: 90px; padding: 6px 8px; border-radius: 10px; }

  .dbox-games-page #tableWrap th.col-id, .dbox-games-page #tableWrap td.col-id { width: 70px; }
  .dbox-games-page #tableWrap th.col-provider, .dbox-games-page #tableWrap td.col-provider { width: 240px; }
  .dbox-games-page #tableWrap th.col-code, .dbox-games-page #tableWrap td.col-code { width: 220px; }
  .dbox-games-page #tableWrap th.col-name, .dbox-games-page #tableWrap td.col-name { width: 320px; }
  .dbox-games-page #tableWrap th.col-hot, .dbox-games-page #tableWrap td.col-hot { width: 120px; }
  .dbox-games-page #tableWrap th.col-active, .dbox-games-page #tableWrap td.col-active { width: 140px; }
  .dbox-games-page #tableWrap th.col-curs, .dbox-games-page #tableWrap td.col-curs { width: 140px; }
  .dbox-games-page #tableWrap th.col-sort, .dbox-games-page #tableWrap td.col-sort { width: 130px; }
  .dbox-games-page #tableWrap th.col-seen, .dbox-games-page #tableWrap td.col-seen { width: 180px; }
  .dbox-games-page #tableWrap th.col-actions, .dbox-games-page #tableWrap td.col-actions { width: 110px; }
</style>

<div class="app dbox-games-page">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
      <div>
        <div style="font-size:22px; font-weight:800;">DBOX Games</div>
        <div style="opacity:.85; margin-top:4px;">
          Total: <strong id="totalCount">{{ $stats['total'] ?? $games->total() }}</strong>
          <span style="margin-left:10px;">Active: <strong id="activeCount">{{ $stats['active'] ?? '0' }}</strong></span>
          <span style="margin-left:10px;">Hot: <strong id="hotCount">{{ $stats['hot'] ?? '0' }}</strong></span>
        </div>
      </div>

      <div style="display:flex; gap:8px; align-items:flex-end;">
        <a id="btnExport" class="btn" href="{{ route('admin.dbox.games.export.csv') }}">Export CSV</a>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">

        <div style="flex:1; min-width:260px;">
          <label class="label">Search</label>
          <input id="q" class="input" placeholder="game code / name" value="{{ $filters['q'] ?? '' }}" />
        </div>

        <div style="width:240px;">
          <label class="label">Provider</label>
          <select id="provider_id" class="input">
            <option value="">All</option>
            @foreach ($providers as $p)
              <option value="{{ $p->id }}" {{ (string)($filters['provider_id'] ?? '') === (string)$p->id ? 'selected':'' }}>
                {{ $p->name }} ({{ $p->code }})
              </option>
            @endforeach
          </select>
        </div>

        <div style="width:150px;">
          <label class="label">Currency</label>
          <select id="currency" class="input">
            <option value="">All</option>
            @foreach ($currencies as $c)
              <option value="{{ $c }}" {{ (string)($filters['currency'] ?? '') === (string)$c ? 'selected':'' }}>{{ $c }}</option>
            @endforeach
          </select>
        </div>

        <div style="width:160px;">
          <label class="label">Active</label>
          <select id="active" class="input">
            <option value="all" {{ ($filters['active'] ?? 'all')==='all'?'selected':'' }}>All</option>
            <option value="1" {{ ($filters['active'] ?? 'all')==='1'?'selected':'' }}>Active</option>
            <option value="0" {{ ($filters['active'] ?? 'all')==='0'?'selected':'' }}>Inactive</option>
          </select>
        </div>

        <div style="width:150px;">
          <label class="label">Hot</label>
          <select id="hot" class="input">
            <option value="all" {{ ($filters['hot'] ?? 'all')==='all'?'selected':'' }}>All</option>
            <option value="1" {{ ($filters['hot'] ?? 'all')==='1'?'selected':'' }}>Hot only</option>
            <option value="0" {{ ($filters['hot'] ?? 'all')==='0'?'selected':'' }}>Not hot</option>
          </select>
        </div>

        <div style="width:170px;">
          <label class="label">Sort</label>
          <select id="sort" class="input">
            <option value="manual" {{ ($filters['sort'] ?? 'manual')==='manual'?'selected':'' }}>manual</option>
            <option value="az" {{ ($filters['sort'] ?? '')==='az'?'selected':'' }}>A → Z</option>
            <option value="za" {{ ($filters['sort'] ?? '')==='za'?'selected':'' }}>Z → A</option>
            <option value="last_seen" {{ ($filters['sort'] ?? '')==='last_seen'?'selected':'' }}>last seen</option>
          </select>
        </div>

        <div>
          <button id="btnSearch" class="btn" type="button">Search</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div id="tableWrap">
        @include('admins.dbox.games.partials.table', ['games' => $games])
      </div>

      <div id="paginationWrap" style="margin-top:12px;">
        {!! $games->links('vendor.pagination.admin') !!}
      </div>
    </div>

    <div id="modalBackdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9998;"></div>

    <div id="modal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:24px;">
      <div class="card" style="width:min(1150px, 98vw); max-height:88vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
          <div style="font-size:18px; font-weight:800;" id="modalTitle">Game</div>
          <button class="btn btn-danger" type="button" id="modalClose">Close</button>
        </div>
        <div id="modalBody" style="margin-top:12px; opacity:.95;">Loading...</div>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  const el = (id) => document.getElementById(id);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const MODAL_TPL  = @json(route('admin.dbox.games.modal', ['game' => '__ID__']));
  const UPDATE_TPL = @json(route('admin.dbox.games.update', ['game' => '__ID__']));
  const TOGGLE_TPL = @json(route('admin.dbox.games.toggleActive', ['game' => '__ID__']));
  const CUR_TPL    = @json(route('admin.dbox.games.currencies.update', ['game' => '__ID__']));

  const filterIds = ['q','provider_id','currency','active','hot','sort'];
  const inputs = filterIds.map(el);
  let timer = null;
  let lastUrl = null;

  function paramsFromUI() {
    const params = new URLSearchParams();

    const q = el('q').value.trim();
    const provider_id = el('provider_id').value;
    const currency = el('currency').value;
    const active = el('active').value;
    const hot = el('hot').value;
    const sort = el('sort').value;

    if (q) params.set('q', q);
    if (provider_id) params.set('provider_id', provider_id);
    if (currency) params.set('currency', currency);
    if (active !== 'all') params.set('active', active);
    if (hot !== 'all') params.set('hot', hot);
    if (sort && sort !== 'manual') params.set('sort', sort);

    return params;
  }

  function buildSearchUrl(pageUrl) {
    if (pageUrl) return pageUrl;
    const base = "{{ route('admin.dbox.games.search') }}";
    const params = paramsFromUI();
    return params.toString() ? (base + "?" + params.toString()) : base;
  }

  function updateExportLink() {
    const base = "{{ route('admin.dbox.games.export.csv') }}";
    const params = paramsFromUI();
    el('btnExport').href = params.toString() ? (base + "?" + params.toString()) : base;
  }

  async function fetchJsonSafe(url, options) {
    const res = await fetch(url, Object.assign({
      credentials: 'same-origin',
      headers: Object.assign({
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      }, options?.headers || {})
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
    lastUrl = url;

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
    if (r.data.stats?.active !== undefined) el('activeCount').textContent = r.data.stats.active;
    if (r.data.stats?.hot !== undefined) el('hotCount').textContent = r.data.stats.hot;

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

  function closeModal() {
    el('modalBackdrop').style.display = 'none';
    el('modal').style.display = 'none';
  }

  function urlTpl(tpl, id) { return tpl.replace('__ID__', String(id)); }

  async function openModal(id) {
    el('modalBackdrop').style.display = 'block';
    el('modal').style.display = 'flex';
    el('modalTitle').textContent = `Game #${id}`;
    el('modalBody').innerHTML = 'Loading...';

    const r = await fetchJsonSafe(urlTpl(MODAL_TPL, id));
    if (!r.ok || !r.data || typeof r.data.html === 'undefined') {
      el('modalBody').innerHTML = `Failed to load modal (HTTP ${r.status}).`;
      return;
    }

    el('modalBody').innerHTML = r.data.html;
    bindModalForms(id);
  }

  function showInlineMsg(targetEl, msg, ok=true) {
    if (!targetEl) return;
    targetEl.textContent = msg;
    targetEl.style.opacity = '1';
    targetEl.style.color = ok ? 'rgba(52,211,153,.95)' : 'rgba(251,113,133,.95)';
    setTimeout(() => { targetEl.style.opacity = '.85'; }, 1200);
  }

  function bindModalForms(id) {
    const form = document.getElementById('gameForm');
    const msg = document.getElementById('gameFormMsg');

    if (form) {
      form.onsubmit = async (e) => {
        e.preventDefault();
        if (msg) msg.textContent = 'Saving...';

        const fd = new FormData(form);

        const r = await fetchJsonSafe(urlTpl(UPDATE_TPL, id), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf },
          body: fd,
        });

        if (!r.ok) {
          showInlineMsg(msg, (r.data?.message) || `Save failed (HTTP ${r.status})`, false);
          return;
        }

        showInlineMsg(msg, r.data?.message || 'Saved.', true);
        await refreshList(lastUrl);
        await openModal(id);
      };
    }

    const curForm = document.getElementById('currencyForm');
    const curMsg = document.getElementById('currencyFormMsg');

    if (curForm) {
      curForm.onsubmit = async (e) => {
        e.preventDefault();
        if (curMsg) curMsg.textContent = 'Updating...';

        const fd = new FormData(curForm);

        const r = await fetchJsonSafe(urlTpl(CUR_TPL, id), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf },
          body: fd,
        });

        if (!r.ok) {
          showInlineMsg(curMsg, (r.data?.message) || `Update failed (HTTP ${r.status})`, false);
          return;
        }

        showInlineMsg(curMsg, r.data?.message || 'Updated.', true);
        await refreshList(lastUrl);
        await openModal(id);
      };
    }
  }

  function bindListActions() {
    document.querySelectorAll('[data-view-game]').forEach(btn => {
      btn.onclick = () => openModal(btn.getAttribute('data-game-id'));
    });

    document.querySelectorAll('[data-toggle-game]').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.getAttribute('data-game-id');
        const r = await fetchJsonSafe(urlTpl(TOGGLE_TPL, id), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf },
        });
        if (r.ok) await refreshList(lastUrl);
      };
    });

    document.querySelectorAll('[data-sort-game]').forEach(inp => {
      inp.onchange = async () => {
        const id = inp.getAttribute('data-game-id');
        const sort = inp.value;

        const fd = new FormData();
        fd.set('sort_order', sort);

        const r = await fetchJsonSafe(urlTpl(UPDATE_TPL, id), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf },
          body: fd,
        });

        if (r.ok) await refreshList(lastUrl);
      };
    });
  }

  el('btnSearch').addEventListener('click', () => refreshList());
  inputs.forEach(i => {
    if (!i) return;
    i.addEventListener('input', debounceRefresh);
    i.addEventListener('change', () => refreshList());
  });

  el('modalClose').addEventListener('click', closeModal);
  el('modalBackdrop').addEventListener('click', closeModal);

  bindListActions();
  bindListPagination();
  updateExportLink();
})();
</script>
@endsection
