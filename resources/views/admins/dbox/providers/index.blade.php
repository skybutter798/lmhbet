@extends('admins.layout')

@section('title', 'DBOX Providers')

@section('body')
<style>
  .dbox-providers-page #tableWrap{
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

  .dbox-providers-page #tableWrap table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    min-width:1200px;
    table-layout:fixed;
  }

  .dbox-providers-page #tableWrap thead th{
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

  .dbox-providers-page #tableWrap th,
  .dbox-providers-page #tableWrap td{
    padding: 10px 10px;
    white-space:nowrap !important;
    overflow:hidden;
    text-overflow:ellipsis;
    vertical-align:middle;
    border-bottom: 1px solid var(--tbl-border);
    font-size: 13px;
    color: rgba(255,255,255,.88);
  }

  .dbox-providers-page #tableWrap th:not(:last-child),
  .dbox-providers-page #tableWrap td:not(:last-child){
    border-right: 1px solid rgba(255,255,255,.06);
  }

  .dbox-providers-page #tableWrap tbody tr:nth-child(odd){ background: var(--tbl-row-a); }
  .dbox-providers-page #tableWrap tbody tr:nth-child(even){ background: var(--tbl-row-b); }
  .dbox-providers-page #tableWrap tbody tr:hover{ background: var(--tbl-hover); }
  .dbox-providers-page #tableWrap tbody tr:last-child td{ border-bottom: 0; }

  .dbox-providers-page #tableWrap .clip{
    display:block;
    width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  .dbox-providers-page #tableWrap .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 12px;
    opacity: .95;
  }

  .dbox-providers-page #tableWrap td .sub{
    display:block;
    margin-top:2px;
    font-size: 12px;
    color: var(--tbl-text-dim);
    opacity: .95;
  }

  .dbox-providers-page #tableWrap .pill{
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
  .dbox-providers-page #tableWrap .dot{
    width:8px; height:8px;
    border-radius:999px;
    background: rgba(255,255,255,.55);
    flex: 0 0 auto;
  }
  .dbox-providers-page #tableWrap .on  .dot{ background:#34d399; }
  .dbox-providers-page #tableWrap .off .dot{ background:#fb7185; }

  .dbox-providers-page #tableWrap input.inline-num{
    width: 90px;
    padding: 6px 8px;
    border-radius: 10px;
  }

  .dbox-providers-page #tableWrap th.col-id, .dbox-providers-page #tableWrap td.col-id { width: 70px; }
  .dbox-providers-page #tableWrap th.col-code, .dbox-providers-page #tableWrap td.col-code { width: 160px; }
  .dbox-providers-page #tableWrap th.col-name, .dbox-providers-page #tableWrap td.col-name { width: 260px; }
  .dbox-providers-page #tableWrap th.col-active, .dbox-providers-page #tableWrap td.col-active { width: 140px; }
  .dbox-providers-page #tableWrap th.col-games, .dbox-providers-page #tableWrap td.col-games { width: 140px; }
  .dbox-providers-page #tableWrap th.col-sort, .dbox-providers-page #tableWrap td.col-sort { width: 130px; }
  .dbox-providers-page #tableWrap th.col-sync, .dbox-providers-page #tableWrap td.col-sync { width: 180px; }
  .dbox-providers-page #tableWrap th.col-actions, .dbox-providers-page #tableWrap td.col-actions { width: 110px; }
</style>

<div class="app dbox-providers-page">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
      <div>
        <div style="font-size:22px; font-weight:800;">DBOX Providers</div>
        <div style="opacity:.85; margin-top:4px;">
          Total: <strong id="totalCount">{{ $stats['total'] ?? $providers->total() }}</strong>
          <span style="margin-left:10px;">Active: <strong id="activeCount">{{ $stats['active'] ?? '0' }}</strong></span>
        </div>
      </div>

      <div style="display:flex; gap:8px; align-items:flex-end;">
        <a id="btnExport" class="btn" href="{{ route('admin.dbox.providers.export.csv') }}">Export CSV</a>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">

        <div style="flex:1; min-width:260px;">
          <label class="label">Search</label>
          <input id="q" class="input" placeholder="code / name" value="{{ $filters['q'] ?? '' }}" />
        </div>

        <div style="width:160px;">
          <label class="label">Active</label>
          <select id="active" class="input">
            <option value="all" {{ ($filters['active'] ?? 'all')==='all'?'selected':'' }}>All</option>
            <option value="1" {{ ($filters['active'] ?? 'all')==='1'?'selected':'' }}>Active</option>
            <option value="0" {{ ($filters['active'] ?? 'all')==='0'?'selected':'' }}>Inactive</option>
          </select>
        </div>

        <div style="width:170px;">
          <label class="label">Sort</label>
          <select id="sort" class="input">
            <option value="manual" {{ ($filters['sort'] ?? 'manual')==='manual'?'selected':'' }}>manual</option>
            <option value="az" {{ ($filters['sort'] ?? '')==='az'?'selected':'' }}>A → Z</option>
            <option value="za" {{ ($filters['sort'] ?? '')==='za'?'selected':'' }}>Z → A</option>
            <option value="synced" {{ ($filters['sort'] ?? '')==='synced'?'selected':'' }}>last synced</option>
          </select>
        </div>

        <div>
          <button id="btnSearch" class="btn" type="button">Search</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div id="tableWrap">
        @include('admins.dbox.providers.partials.table', ['providers' => $providers])
      </div>

      <div id="paginationWrap" style="margin-top:12px;">
        {!! $providers->links('vendor.pagination.admin') !!}
      </div>
    </div>

    <div id="modalBackdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9998;"></div>

    <div id="modal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:24px;">
      <div class="card" style="width:min(1100px, 98vw); max-height:88vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
          <div style="font-size:18px; font-weight:800;" id="modalTitle">Provider</div>
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

  const MODAL_TPL  = @json(route('admin.dbox.providers.modal', ['provider' => '__ID__']));
  const UPDATE_TPL = @json(route('admin.dbox.providers.update', ['provider' => '__ID__']));
  const TOGGLE_TPL = @json(route('admin.dbox.providers.toggleActive', ['provider' => '__ID__']));

  const filterIds = ['q','active','sort'];
  const inputs = filterIds.map(el);
  let timer = null;
  let lastUrl = null;

  function paramsFromUI() {
    const params = new URLSearchParams();
    const q = el('q').value.trim();
    const active = el('active').value;
    const sort = el('sort').value;

    if (q) params.set('q', q);
    if (active !== 'all') params.set('active', active);
    if (sort && sort !== 'manual') params.set('sort', sort);

    return params;
  }

  function buildSearchUrl(pageUrl) {
    if (pageUrl) return pageUrl;
    const base = "{{ route('admin.dbox.providers.search') }}";
    const params = paramsFromUI();
    return params.toString() ? (base + "?" + params.toString()) : base;
  }

  function updateExportLink() {
    const base = "{{ route('admin.dbox.providers.export.csv') }}";
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

  function buildModalUrl(id) {
    return MODAL_TPL.replace('__ID__', String(id));
  }
  function buildUpdateUrl(id) {
    return UPDATE_TPL.replace('__ID__', String(id));
  }
  function buildToggleUrl(id) {
    return TOGGLE_TPL.replace('__ID__', String(id));
  }

  async function openModal(id) {
    el('modalBackdrop').style.display = 'block';
    el('modal').style.display = 'flex';
    el('modalTitle').textContent = `Provider #${id}`;
    el('modalBody').innerHTML = 'Loading...';

    const r = await fetchJsonSafe(buildModalUrl(id));

    if (!r.ok || !r.data || typeof r.data.html === 'undefined') {
      el('modalBody').innerHTML = `Failed to load modal (HTTP ${r.status}).`;
      return;
    }

    el('modalBody').innerHTML = r.data.html;
    bindModalForm(id);
  }

  function showInlineMsg(targetEl, msg, ok=true) {
    if (!targetEl) return;
    targetEl.textContent = msg;
    targetEl.style.opacity = '1';
    targetEl.style.color = ok ? 'rgba(52,211,153,.95)' : 'rgba(251,113,133,.95)';
    setTimeout(() => { targetEl.style.opacity = '.85'; }, 1200);
  }

  function bindModalForm(id) {
    const form = document.getElementById('providerForm');
    const msg = document.getElementById('providerFormMsg');
    if (!form) return;

    form.onsubmit = async (e) => {
      e.preventDefault();
      if (msg) msg.textContent = 'Saving...';

      const fd = new FormData(form);

      const r = await fetchJsonSafe(buildUpdateUrl(id), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf },
        body: fd,
      });

      if (!r.ok) {
        const err = (r.data?.message) || `Save failed (HTTP ${r.status})`;
        showInlineMsg(msg, err, false);
        return;
      }

      showInlineMsg(msg, r.data?.message || 'Saved.', true);
      await refreshList(lastUrl);
      await openModal(id); // reload modal content
    };
  }

  function bindListActions() {
    document.querySelectorAll('[data-view-provider]').forEach(btn => {
      btn.onclick = () => openModal(btn.getAttribute('data-provider-id'));
    });

    document.querySelectorAll('[data-toggle-provider]').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.getAttribute('data-provider-id');
        const r = await fetchJsonSafe(buildToggleUrl(id), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf },
        });
        if (r.ok) await refreshList(lastUrl);
      };
    });

    document.querySelectorAll('[data-sort-provider]').forEach(inp => {
      inp.onchange = async () => {
        const id = inp.getAttribute('data-provider-id');
        const sort = inp.value;

        const fd = new FormData();
        fd.set('sort_order', sort);

        const r = await fetchJsonSafe(buildUpdateUrl(id), {
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
