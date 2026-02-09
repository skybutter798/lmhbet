@extends('admins.layout')

@section('title', 'KYC Approvals')

@section('body')
<div class="app">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar">
      <div style="font-size:22px; font-weight:800;">KYC Approvals</div>
      <div style="opacity:.85;">Total: <strong id="kycTotal">{{ $subs->total() }}</strong></div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <div style="flex:1; min-width:240px;">
          <label class="label">Search</label>
          <input id="q" class="input" placeholder="username / email / phone" />
        </div>

        <div style="width:200px;">
          <label class="label">Status</label>
          <select id="status" class="input">
            <option value="pending">pending</option>
            <option value="approved">approved</option>
            <option value="rejected">rejected</option>
            <option value="cancelled">cancelled</option>
            <option value="all">all</option>
          </select>
        </div>

        <div style="display:flex; align-items:flex-end;">
          <button id="btnSearch" class="btn" type="button">Search</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div id="tableWrap">@include('admins.kyc.partials.table', ['subs' => $subs])</div>
      <div id="paginationWrap" style="margin-top:12px;">{{ $subs->links() }}</div>
    </div>
  </div>
</div>

<script>
(function () {
  const el = (id) => document.getElementById(id);
  const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  function buildUrl() {
    const params = new URLSearchParams();
    const q = el('q').value.trim();
    const s = el('status').value;

    if (q) params.set('q', q);
    if (s) params.set('status', s);

    const base = "{{ route('admin.kyc.search') }}";
    return params.toString() ? (base + "?" + params.toString()) : base;
  }

  async function post(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With':'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
        'Content-Type':'application/json',
      },
      body: JSON.stringify(payload || {})
    });
    return await res.json();
  }

  async function fetchAndRender(href) {
    const res = await fetch(href || buildUrl(), { headers: { 'X-Requested-With':'XMLHttpRequest' }});
    const data = await res.json();

    el('tableWrap').innerHTML = data.html;
    el('paginationWrap').innerHTML = data.pagination || '';
    if (data.total !== undefined) el('kycTotal').textContent = data.total;

    bindActions();
    bindPagination();
  }

  function bindActions() {
    document.querySelectorAll('[data-approve]').forEach(b => {
      b.onclick = async () => {
        b.disabled = true;
        await post(b.dataset.url);
        b.disabled = false;
        fetchAndRender();
      };
    });

    document.querySelectorAll('[data-reject]').forEach(b => {
      b.onclick = async () => {
        const remarks = prompt('Reject remarks (optional):') || '';
        b.disabled = true;
        await post(b.dataset.url, { remarks });
        b.disabled = false;
        fetchAndRender();
      };
    });
  }

  function bindPagination() {
    el('paginationWrap').querySelectorAll('a').forEach(a => {
      a.onclick = async (e) => {
        e.preventDefault();
        await fetchAndRender(a.href);
      };
    });
  }

  el('btnSearch').onclick = () => fetchAndRender();
  el('q').addEventListener('input', () => fetchAndRender());
  el('status').addEventListener('change', () => fetchAndRender());

  bindActions();
  bindPagination();
})();
</script>
@endsection
