@extends('admins.layout')

@section('title', 'DBOX Game Sorting')

@section('body')
<style>
  .dbox-game-sort-page .panel{display:flex;gap:12px;flex-wrap:wrap;}
  .dbox-game-sort-page #listWrap{
    --tbl-border: rgba(255,255,255,.10);
    --tbl-border-strong: rgba(255,255,255,.16);
    --tbl-head-bg: rgba(255,255,255,.06);
    --tbl-row-a: rgba(255,255,255,.02);
    --tbl-row-b: rgba(255,255,255,.00);
    --tbl-hover: rgba(255,255,255,.08);

    overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;
    padding-bottom:6px;border:1px solid var(--tbl-border);
    border-radius:12px;background:rgba(0,0,0,.10);
  }
  .dbox-game-sort-page #listWrap table{width:100%;border-collapse:separate;border-spacing:0;min-width:1200px;table-layout:fixed;}
  .dbox-game-sort-page #listWrap thead th{
    position:sticky;top:0;z-index:2;background:var(--tbl-head-bg);
    backdrop-filter:blur(6px);border-bottom:1px solid var(--tbl-border-strong);
    color:rgba(255,255,255,.92);font-weight:800;font-size:12px;letter-spacing:.2px;text-transform:uppercase;
  }
  .dbox-game-sort-page #listWrap th,.dbox-game-sort-page #listWrap td{
    padding:10px 10px;white-space:nowrap!important;overflow:hidden;text-overflow:ellipsis;
    vertical-align:middle;border-bottom:1px solid rgba(255,255,255,.10);
    font-size:13px;color:rgba(255,255,255,.88);
  }
  .dbox-game-sort-page #listWrap th:not(:last-child),.dbox-game-sort-page #listWrap td:not(:last-child){
    border-right:1px solid rgba(255,255,255,.06);
  }
  .dbox-game-sort-page #listWrap tbody tr:nth-child(odd){background:var(--tbl-row-a);}
  .dbox-game-sort-page #listWrap tbody tr:nth-child(even){background:var(--tbl-row-b);}
  .dbox-game-sort-page #listWrap tbody tr:hover{background:var(--tbl-hover);}

  .dbox-game-sort-page .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-size:12px;opacity:.95;}
  .dbox-game-sort-page .hint{opacity:.8;font-size:12px;}
  .dbox-game-sort-page .row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;}
  .dbox-game-sort-page .box{flex:1;min-width:320px;}
  .dbox-game-sort-page .picker{position:relative;}
  .dbox-game-sort-page .dropdown{
    position:absolute;top:calc(100% + 6px);left:0;right:0;
    background:rgba(10,18,32,.98);border:1px solid rgba(255,255,255,.10);
    border-radius:12px;overflow:hidden;z-index:50;display:none;max-height:260px;overflow:auto;
  }
  .dbox-game-sort-page .dropdown .item{padding:10px 10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08);}
  .dbox-game-sort-page .dropdown .item:hover{background:rgba(255,255,255,.08);}
  .dbox-game-sort-page .dropdown .small{font-size:12px;opacity:.8;margin-top:2px;}
  
    .dbox-game-sort-page .dragcell { text-align:center; }
    .dbox-game-sort-page .drag-handle{
      display:inline-flex; align-items:center; justify-content:center;
      width:34px; height:30px;
      border:1px solid rgba(255,255,255,.12);
      background:rgba(255,255,255,.06);
      color:rgba(255,255,255,.9);
      border-radius:8px;
      cursor:grab;
      user-select:none;
    }
    .dbox-game-sort-page .drag-handle:active{ cursor:grabbing; }
    .dbox-game-sort-page tr.is-dragging{ opacity:.5; }
    .dbox-game-sort-page tr.drag-over{ outline:2px dashed rgba(52,211,153,.7); outline-offset:-2px; }

</style>

<div class="app dbox-game-sort-page">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
      <div>
        <div style="font-size:22px; font-weight:800;">DBOX Game Sorting</div>
        <div class="hint" style="margin-top:4px;">
          This uses <strong>simple order</strong>: 0..N-1. Place Before/After will <strong>shift</strong> other rows so it never duplicates.
        </div>
        <div class="hint" style="margin-top:4px;">
          Nudge Up/Down = move one step (swap with neighbor).
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div class="row">
        <div style="width:340px;">
          <label class="label">Provider</label>
          <select id="provider_id" class="input">
            @foreach ($providers as $p)
              <option value="{{ $p->id }}" {{ (string)$providerId === (string)$p->id ? 'selected':'' }}>
                {{ $p->name }} ({{ $p->code }})
              </option>
            @endforeach
          </select>
        </div>

        <div style="flex:1; min-width:260px;">
          <label class="label">List Search (table)</label>
          <input id="list_q" class="input" placeholder="filter list by code/name" />
        </div>

        <div style="width:140px;">
          <label class="label">Limit</label>
          <select id="limit" class="input">
            <option value="200">200</option>
            <option value="300">300</option>
            <option value="500">500</option>
          </select>
        </div>

        <div>
          <button id="btnLoad" class="btn" type="button">Load</button>
        </div>

        <div style="margin-left:auto; display:flex; gap:8px; align-items:flex-end;">
          <button id="btnRenumber" class="btn btn-danger" type="button">Normalize 0..N-1</button>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div class="panel">
        <div class="box">
          <label class="label">Move Game</label>
          <div class="picker">
            <input id="move_game" class="input" placeholder="type to search game..." autocomplete="off" />
            <div id="move_game_dd" class="dropdown"></div>
          </div>
          <div class="hint" style="margin-top:6px;">Pick the game you want to move.</div>
        </div>

        <div class="box">
          <label class="label">Reference Game</label>
          <div class="picker">
            <input id="ref_game" class="input" placeholder="type to search reference..." autocomplete="off" />
            <div id="ref_game_dd" class="dropdown"></div>
          </div>
          <div class="hint" style="margin-top:6px;">Pick the game to place before/after.</div>
        </div>

        <div style="min-width:320px;">
          <label class="label">Actions</label>
          <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <button class="btn" type="button" id="btnTop">Move Top (0)</button>
            <button class="btn" type="button" id="btnBottom">Move Bottom (max)</button>
            <button class="btn" type="button" id="btnBefore">Place Before</button>
            <button class="btn" type="button" id="btnAfter">Place After</button>
            <button class="btn" type="button" id="btnUp">Nudge Up (-1)</button>
            <button class="btn" type="button" id="btnDown">Nudge Down (+1)</button>
          </div>
          <div id="actionMsg" class="hint" style="margin-top:10px;"></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px;">
        <div style="font-weight:800;">Provider Order (page)</div>
        <div class="hint">Total: <strong id="totalCount">0</strong></div>
      </div>

      <div id="listWrap">
        <div style="padding:12px; opacity:.85;">Click “Load” to fetch games.</div>
      </div>

      <div style="display:flex; gap:8px; margin-top:12px; align-items:center;">
        <button id="btnPrev" class="btn" type="button">Prev</button>
        <button id="btnNext" class="btn" type="button">Next</button>
        <div class="hint">Page <strong id="pageNo">1</strong> / <strong id="lastPage">1</strong></div>
      </div>
    </div>

  </div>
</div>

<script>
(function(){
  const el = (id) => document.getElementById(id);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const URL_LIST = @json(route('admin.dbox.games.sort.list'));
  const URL_AUTO = @json(route('admin.dbox.games.sort.autocomplete'));
  const URL_MOVE = @json(route('admin.dbox.games.sort.move'));
  const URL_RENUM = @json(route('admin.dbox.games.sort.renumber'));
  const URL_REORDER = @json(route('admin.dbox.games.sort.reorder'));


  let state = {
    provider_id: el('provider_id').value,
    page: 1,
    last_page: 1,
    limit: parseInt(el('limit').value, 10),
    q: '',
    move_game_id: null,
    ref_game_id: null,
  };

  function setMsg(msg, ok=true){
    const m = el('actionMsg');
    m.textContent = msg;
    m.style.color = ok ? 'rgba(52,211,153,.95)' : 'rgba(251,113,133,.95)';
  }

  async function fetchJson(url, options){
    const res = await fetch(url, Object.assign({
      credentials: 'same-origin',
      headers: Object.assign({
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      }, options?.headers || {})
    }, options || {}));

    const ct = (res.headers.get('content-type') || '').toLowerCase();
    const data = ct.includes('application/json') ? await res.json().catch(() => null) : null;
    return { ok: res.ok, status: res.status, data };
  }

  async function loadList(){
    state.provider_id = el('provider_id').value;
    state.q = el('list_q').value.trim();
    state.limit = parseInt(el('limit').value, 10);

    const params = new URLSearchParams();
    params.set('provider_id', state.provider_id);
    params.set('page', String(state.page));
    params.set('limit', String(state.limit));
    if (state.q) params.set('q', state.q);

    const r = await fetchJson(URL_LIST + "?" + params.toString());
    if (!r.ok || !r.data?.html){
      el('listWrap').innerHTML = `<div style="padding:12px;">Failed to load list (HTTP ${r.status}).</div>`;
      return;
    }

    el('listWrap').innerHTML = r.data.html;
    el('totalCount').textContent = r.data.total ?? '0';
    el('pageNo').textContent = r.data.page ?? '1';
    el('lastPage').textContent = r.data.last_page ?? '1';

    state.last_page = parseInt(r.data.last_page || 1, 10);
    // ✅ enable drag behavior on newly rendered rows
    enableDragReorder();
  }
  
  function enableDragReorder(){
      const table = document.getElementById('sortTable');
      if (!table) return;
    
      const tbody = table.querySelector('tbody');
      if (!tbody) return;
    
      let draggingRow = null;
    
      // mark draggable on handle only
      tbody.querySelectorAll('tr').forEach(tr => {
        const handle = tr.querySelector('.drag-handle');
        if (!handle) return;
    
        tr.draggable = false; // only start drag from handle
        handle.addEventListener('mousedown', () => { tr.draggable = true; });
        handle.addEventListener('mouseup', () => { tr.draggable = false; });
        handle.addEventListener('mouseleave', () => { tr.draggable = false; });
    
        tr.addEventListener('dragstart', (e) => {
          draggingRow = tr;
          tr.classList.add('is-dragging');
          // needed for firefox
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', tr.dataset.gameId || '');
        });
    
        tr.addEventListener('dragend', async () => {
          tr.classList.remove('is-dragging');
          tbody.querySelectorAll('tr').forEach(x => x.classList.remove('drag-over'));
          draggingRow = null;
    
          // After drop, auto-save this page order
          await saveCurrentPageOrder();
        });
    
        tr.addEventListener('dragover', (e) => {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          if (!draggingRow || tr === draggingRow) return;
    
          tr.classList.add('drag-over');
    
          // reorder: insert draggingRow before/after based on mouse position
          const rect = tr.getBoundingClientRect();
          const after = (e.clientY - rect.top) > (rect.height / 2);
          if (after) {
            if (tr.nextSibling !== draggingRow) tbody.insertBefore(draggingRow, tr.nextSibling);
          } else {
            if (tr !== draggingRow.nextSibling) tbody.insertBefore(draggingRow, tr);
          }
        });
    
        tr.addEventListener('dragleave', () => {
          tr.classList.remove('drag-over');
        });
    
        tr.addEventListener('drop', (e) => {
          e.preventDefault();
          tr.classList.remove('drag-over');
        });
      });
    }
    
    async function saveCurrentPageOrder(){
      const table = document.getElementById('sortTable');
      if (!table) return;
    
      const ids = Array.from(table.querySelectorAll('tbody tr[data-game-id]'))
        .map(tr => parseInt(tr.dataset.gameId, 10))
        .filter(n => Number.isFinite(n));
    
      if (ids.length < 2) return;
    
      const fd = new FormData();
      fd.set('provider_id', el('provider_id').value);
      ids.forEach((id, idx) => fd.append(`ordered_ids[${idx}]`, String(id)));
    
      setMsg('Saving order...', true);
    
      const r = await fetchJson(URL_REORDER, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf },
        body: fd,
      });
    
      if (!r.ok || !r.data?.ok){
        setMsg(r.data?.message || `Save failed (HTTP ${r.status}).`, false);
        return;
      }
    
      setMsg(r.data?.message || 'Order saved.', true);
    
      // Refresh list to show updated sort_order values (optional but recommended)
      await loadList();
    }


  function debounce(fn, ms){
    let t=null;
    return (...args) => {
      if (t) clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  }

  function escapeHtml(s){
    return String(s ?? '')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function attachPicker(inputId, ddId, onPick){
    const input = el(inputId);
    const dd = el(ddId);

    function hide(){ dd.style.display='none'; dd.innerHTML=''; }
    function show(){ dd.style.display='block'; }

    function render(items){
      dd.innerHTML = '';
      if (!items.length){
        dd.innerHTML = `<div class="item" style="opacity:.8;">No results</div>`;
        show();
        return;
      }
      items.forEach(it => {
        const div = document.createElement('div');
        div.className = 'item';
        div.innerHTML = `
          <div><strong class="mono">${escapeHtml(it.code)}</strong> — ${escapeHtml(it.name)}</div>
          <div class="small">id: ${it.id} • sort: ${it.sort_order} • ${it.hot ? 'hot' : 'not hot'} • ${it.is_active ? 'active' : 'inactive'}</div>
        `;
        div.onclick = () => {
          input.value = `${it.code} — ${it.name}`;
          hide();
          onPick(it);
        };
        dd.appendChild(div);
      });
      show();
    }

    const run = debounce(async () => {
      const q = input.value.trim();
      if (q.length < 1){ hide(); return; }

      const params = new URLSearchParams();
      params.set('provider_id', el('provider_id').value);
      params.set('q', q);
      params.set('limit', '20');

      const r = await fetchJson(URL_AUTO + "?" + params.toString());
      if (!r.ok || !r.data?.items){ hide(); return; }
      render(r.data.items);
    }, 250);

    input.addEventListener('input', run);
    input.addEventListener('focus', run);

    document.addEventListener('click', (e) => {
      if (!dd.contains(e.target) && e.target !== input) hide();
    });
  }

  attachPicker('move_game', 'move_game_dd', (it) => {
    state.move_game_id = it.id;
    setMsg(`Move game: #${it.id} (sort ${it.sort_order})`, true);
  });

  attachPicker('ref_game', 'ref_game_dd', (it) => {
    state.ref_game_id = it.id;
    setMsg(`Reference: #${it.id} (sort ${it.sort_order})`, true);
  });

  async function doMove(mode){
    const provider_id = el('provider_id').value;

    if (!state.move_game_id){
      setMsg('Pick “Move Game” first.', false);
      return;
    }

    if ((mode === 'before' || mode === 'after') && !state.ref_game_id){
      setMsg('Pick “Reference Game” for before/after.', false);
      return;
    }

    const fd = new FormData();
    fd.set('provider_id', provider_id);
    fd.set('game_id', String(state.move_game_id));
    fd.set('mode', mode);
    if (state.ref_game_id) fd.set('ref_id', String(state.ref_game_id));

    setMsg('Working...', true);

    const r = await fetchJson(URL_MOVE, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf },
      body: fd,
    });

    if (!r.ok || !r.data?.ok){
      setMsg(r.data?.message || `Move failed (HTTP ${r.status}).`, false);
      return;
    }

    setMsg(r.data?.message || 'Moved.', true);
    state.page = 1;
    await loadList();
  }

  el('btnTop').onclick = () => doMove('top');
  el('btnBottom').onclick = () => doMove('bottom');
  el('btnBefore').onclick = () => doMove('before');
  el('btnAfter').onclick = () => doMove('after');
  el('btnUp').onclick = () => doMove('nudge_up');
  el('btnDown').onclick = () => doMove('nudge_down');

  el('btnRenumber').onclick = async () => {
    const provider_id = el('provider_id').value;
    if (!confirm('Normalize will rewrite sort_order for ALL games in this provider to 0..N-1. Continue?')) return;

    const fd = new FormData();
    fd.set('provider_id', provider_id);

    setMsg('Normalizing...', true);

    const r = await fetchJson(URL_RENUM, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf },
      body: fd,
    });

    if (!r.ok || !r.data?.ok){
      setMsg(r.data?.message || `Normalize failed (HTTP ${r.status}).`, false);
      return;
    }

    setMsg(r.data?.message || 'Normalized.', true);
    state.page = 1;
    await loadList();
  };

  el('btnLoad').onclick = async () => {
    state.page = 1;
    await loadList();
  };

  el('provider_id').onchange = async () => {
    state.page = 1;
    state.move_game_id = null;
    state.ref_game_id = null;
    el('move_game').value = '';
    el('ref_game').value = '';
    await loadList();
  };

  el('list_q').addEventListener('input', debounce(async () => {
    state.page = 1;
    await loadList();
  }, 350));

  el('limit').onchange = async () => {
    state.page = 1;
    await loadList();
  };

  el('btnPrev').onclick = async () => {
    if (state.page <= 1) return;
    state.page -= 1;
    await loadList();
  };

  el('btnNext').onclick = async () => {
    if (state.page >= state.last_page) return;
    state.page += 1;
    await loadList();
  };

  loadList().catch(()=>{});
})();
</script>
@endsection
