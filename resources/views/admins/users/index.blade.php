@extends('admins.layout')

@section('title', 'User Management')

@section('body')
<style>
  /* =========================
     Users table: single-line rows + horizontal scroll
     ========================= */

  #tableWrap{
    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    padding-bottom:6px;
  }

  /* force table to become wider than container when needed (so scroll appears) */
  #tableWrap table{
    width:100%;
    border-collapse:collapse;
    min-width:1100px; /* adjust if you have more columns */
  }

  /* IMPORTANT: prevent wrapping (no double line rows) */
  #tableWrap th,
  #tableWrap td{
    white-space:nowrap !important;
    overflow-wrap:normal !important;
    word-break:keep-all !important;
    vertical-align:middle;
  }


  /* if you want to clip only text but keep buttons ok: wrap text in .clip span (optional) */
  #tableWrap .clip{
    display:inline-block;
    max-width:260px;
    overflow:hidden;
    text-overflow:ellipsis;
    vertical-align:bottom;
  }

  /* keep action buttons on one line */
  #tableWrap .actions{
    white-space:nowrap !important;
  }
</style>

<div class="app">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar">
      <div style="font-size:22px; font-weight:800;">User Management</div>
      <div style="opacity:.85;">Total: <strong id="totalCount">{{ $users->total() }}</strong></div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <div style="flex:1; min-width:240px;">
          <label class="label">Search</label>
          <input id="q" class="input" placeholder="username / email / phone / referral" />
        </div>

        <div style="width:160px;">
          <label class="label">Status</label>
          <select id="status" class="input">
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div style="width:160px;">
          <label class="label">Banned</label>
          <select id="banned" class="input">
            <option value="all">All</option>
            <option value="banned">Banned</option>
            <option value="not_banned">Not banned</option>
          </select>
        </div>

        <div style="width:160px;">
          <label class="label">Locked</label>
          <select id="locked" class="input">
            <option value="all">All</option>
            <option value="locked">Locked</option>
            <option value="not_locked">Not locked</option>
          </select>
        </div>

        <div style="width:120px;">
          <label class="label">Country</label>
          <input id="country" class="input" placeholder="MY" />
        </div>

        <div style="width:120px;">
          <label class="label">Currency</label>
          <input id="currency" class="input" placeholder="MYR" />
        </div>

        <div style="width:170px;">
          <label class="label">From</label>
          <input id="from" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="width:170px;">
          <label class="label">To</label>
          <input id="to" class="input" placeholder="YYYY-MM-DD" />
        </div>

        <div style="display:flex; align-items:flex-end;">
          <button id="btnSearch" class="btn" type="button">Search</button>
          <a id="btnExport" class="btn" href="{{ route('admin.users.export.csv') }}" style="margin-left:8px; display:inline-block;">
            Export CSV
          </a>
        </div>
      </div>
    </div>

    <div class="card">
      <div id="tableWrap">
        @include('admins.users.partials.table', ['users' => $users])
      </div>

      <div id="paginationWrap" style="margin-top:12px;">
        {!! $users->links('vendor.pagination.admin') !!}
      </div>
    </div>

    <div id="userModalBackdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9998;"></div>

    <div id="userModal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:24px;">
      <div class="card" style="width:min(1100px, 98vw); max-height:88vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
          <div style="font-size:18px; font-weight:800;" id="userModalTitle">User</div>
          <button class="btn btn-danger" type="button" id="userModalClose">Close</button>
        </div>
        <div id="userModalBody" style="margin-top:12px; opacity:.95;">Loading...</div>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  const el = (id) => document.getElementById(id);

  const csrf = () => {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  };

  const MODAL_TPL = @json(route('admin.users.modal', ['user' => '__USER__']));

  const filterIds = ['q','status','banned','locked','country','currency','from','to'];
  const inputs = filterIds.map(el);

  let timer = null;

  function paramsFromUI() {
    const params = new URLSearchParams();

    const q = el('q').value.trim();
    const status = el('status').value;
    const banned = el('banned').value;
    const locked = el('locked').value;
    const country = el('country').value.trim();
    const currency = el('currency').value.trim();
    const from = el('from').value.trim();
    const to = el('to').value.trim();

    if (q) params.set('q', q);
    if (status !== 'all') params.set('status', status);
    if (banned !== 'all') params.set('banned', banned);
    if (locked !== 'all') params.set('locked', locked);
    if (country) params.set('country', country);
    if (currency) params.set('currency', currency);
    if (from) params.set('from', from);
    if (to) params.set('to', to);

    return params;
  }

  function buildSearchUrl(pageUrl) {
    if (pageUrl) return pageUrl;
    const base = "{{ route('admin.users.search') }}";
    const params = paramsFromUI();
    return params.toString() ? (base + "?" + params.toString()) : base;
  }

  function updateExportLink() {
    const base = "{{ route('admin.users.export.csv') }}";
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

  async function postJson(url, bodyObj) {
    return await fetchJsonSafe(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(bodyObj || {}),
    });
  }

  async function postForm(url, formEl) {
    const fd = new FormData(formEl);

    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf(),
      },
      body: fd,
    });

    const contentType = (res.headers.get('content-type') || '').toLowerCase();

    if (contentType.includes('application/json')) {
      const data = await res.json().catch(() => null);
      return { ok: res.ok, status: res.status, data };
    }

    const text = await res.text().catch(() => '');
    return { ok: res.ok, status: res.status, data: null, text };
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

  function closeUserModal() {
    el('userModalBackdrop').style.display = 'none';
    el('userModal').style.display = 'none';
  }

  function buildModalUrl(userId) {
    return MODAL_TPL.replace('__USER__', String(userId));
  }

  async function openUserModal(userId) {
    el('userModalBackdrop').style.display = 'block';
    el('userModal').style.display = 'flex';
    el('userModalTitle').textContent = `User #${userId}`;
    el('userModalBody').innerHTML = 'Loading...';

    const url = buildModalUrl(userId);
    const r = await fetchJsonSafe(url);

    if (!r.ok || !r.data || typeof r.data.html === 'undefined') {
      const snippet = (r.text || '').replace(/</g, '&lt;').slice(0, 300);
      el('userModalBody').innerHTML = `
        <div style="padding:12px;">
          Failed to load user modal (HTTP ${r.status}).<br>
          <div style="opacity:.85; font-size:12px; margin-top:6px;">${r.finalUrl || url}</div>
          ${snippet ? `<pre style="margin-top:10px; padding:10px; background:#0b1220; border:1px solid #1f335c; white-space:pre-wrap; font-size:12px; opacity:.9;">${snippet}</pre>` : ''}
        </div>`;
      return;
    }

    el('userModalBody').innerHTML = r.data.html;
    bindModalActions(userId);
  }

  function bindModalActions(userId) {
    const root = document.getElementById('lmhUserModal');
    if (root) {
      const btns = root.querySelectorAll('.tabBtn');
      const panes = root.querySelectorAll('.tabPane');

      const activate = (name) => {
        btns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
        panes.forEach(p => p.classList.toggle('active', p.dataset.pane === name));
      };

      btns.forEach(b => {
        b.onclick = (e) => {
          e.preventDefault();
          activate(b.dataset.tab);
        };
      });

      const current = root.querySelector('.tabBtn.active')?.dataset?.tab || 'overview';
      activate(current);
    }

    const form = document.getElementById('walletAdjustForm');
    if (form) {
      form.onsubmit = async (e) => {
        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        const r = await postForm(form.action, form);

        if (btn) btn.disabled = false;

        if (!r.ok || (r.data && r.data.ok === false)) {
          const msg = (r.data && r.data.msg) ? r.data.msg : `Failed (HTTP ${r.status})`;
          alert(msg);
          return;
        }

        await openUserModal(userId);
      };
    }

    const txPag = document.getElementById('txPagination');
    const betsPag = document.getElementById('betsPagination');

    if (txPag) txPag.querySelectorAll('a').forEach(a => a.setAttribute('data-tx-page', '1'));
    if (betsPag) betsPag.querySelectorAll('a').forEach(a => a.setAttribute('data-bets-page', '1'));

    document.querySelectorAll('[data-tx-page]').forEach(a => {
      a.onclick = async (e) => {
        e.preventDefault();
        const href = a.getAttribute('href');
        if (!href) return;

        const wrap = document.getElementById('txWrap');
        const pag = document.getElementById('txPagination');

        const r = await fetchJsonSafe(href);
        if (!r.ok || !r.data) return;

        if (wrap) wrap.innerHTML = r.data.html || '';
        if (pag) pag.innerHTML = r.data.pagination || '';

        bindModalActions(userId);
      };
    });

    document.querySelectorAll('[data-bets-page]').forEach(a => {
      a.onclick = async (e) => {
        e.preventDefault();
        const href = a.getAttribute('href');
        if (!href) return;

        const wrap = document.getElementById('betsWrap');
        const pag = document.getElementById('betsPagination');

        const r = await fetchJsonSafe(href);
        if (!r.ok || !r.data) return;

        if (wrap) wrap.innerHTML = r.data.html || '';
        if (pag) pag.innerHTML = r.data.pagination || '';

        bindModalActions(userId);
      };
    });
  }

  function bindListActions() {
    document.querySelectorAll('[data-view-user]').forEach(btn => {
      btn.onclick = () => openUserModal(btn.getAttribute('data-user-id'));
    });

    document.querySelectorAll('[data-toggle-active]').forEach(btn => {
      btn.onclick = async () => {
        btn.disabled = true;
        await postJson(btn.dataset.url);
        btn.disabled = false;
        await refreshList();
      };
    });

    document.querySelectorAll('[data-ban]').forEach(btn => {
      btn.onclick = async () => {
        const reason = prompt('Ban reason (optional):') || '';
        btn.disabled = true;
        await postJson(btn.dataset.url, { ban_reason: reason });
        btn.disabled = false;
        await refreshList();
      };
    });

    document.querySelectorAll('[data-unban]').forEach(btn => {
      btn.onclick = async () => {
        btn.disabled = true;
        await postJson(btn.dataset.url);
        btn.disabled = false;
        await refreshList();
      };
    });

    document.querySelectorAll('[data-lock]').forEach(btn => {
      btn.onclick = async () => {
        const minutes = parseInt(prompt('Lock minutes (default 30):', '30') || '30', 10);
        btn.disabled = true;
        await postJson(btn.dataset.url, { minutes });
        btn.disabled = false;
        await refreshList();
      };
    });

    document.querySelectorAll('[data-unlock]').forEach(btn => {
      btn.onclick = async () => {
        btn.disabled = true;
        await postJson(btn.dataset.url);
        btn.disabled = false;
        await refreshList();
      };
    });
  }

  el('btnSearch').addEventListener('click', () => refreshList());

  inputs.forEach(i => {
    i.addEventListener('input', debounceRefresh);
    i.addEventListener('change', () => refreshList());
  });

  el('userModalClose').addEventListener('click', closeUserModal);
  el('userModalBackdrop').addEventListener('click', closeUserModal);

  bindListActions();
  bindListPagination();
  updateExportLink();
})();
</script>
@endsection
