{{-- /home/lmh/app/resources/views/admins/dbox/images/upload.blade.php --}}

@extends('admins.layout')

@section('title', 'DBOX Image Upload')

@section('body')
<style>
  .dbox-img-page .grid{display:flex;gap:14px;flex-wrap:wrap;}
  .dbox-img-page .left{flex:1;min-width:360px;}
  .dbox-img-page .right{width:420px;min-width:320px;}
  .dbox-img-page .ok{
    background:rgba(52,211,153,.12);
    border:1px solid rgba(52,211,153,.35);
    padding:10px 12px;border-radius:12px;margin-bottom:12px;color:rgba(255,255,255,.92);
  }
  .dbox-img-page .err{
    background:rgba(251,113,133,.12);
    border:1px solid rgba(251,113,133,.35);
    padding:10px 12px;border-radius:12px;margin-bottom:12px;color:rgba(255,255,255,.92);
  }
  .dbox-img-page .hint{font-size:12px;opacity:.8;margin-top:6px;}
  .dbox-img-page #resultSelect{width:100%;min-height:260px;}
  .dbox-img-page .row{margin-bottom:12px;}
  .dbox-img-page .two{display:flex;gap:10px;}
  .dbox-img-page .two>div{flex:1;}
  .dbox-img-page .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size:12px;opacity:.95;
  }
</style>

<div class="app dbox-img-page">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
      <div>
        <div style="font-size:22px; font-weight:800;">DBOX Image Upload</div>
        <div style="opacity:.85; margin-top:4px;">
          Upload provider logos and game thumbnails into
          <span class="mono">public/images/providers</span> or <span class="mono">public/images/games</span>.
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="ok">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="err">
        <ul style="margin:0; padding-left:18px;">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid">
      <div class="card left">
        <form method="POST" action="{{ route('admin.dbox.images.upload.store') }}" enctype="multipart/form-data" id="uploadForm">
          @csrf

          <div class="row">
            <label class="label">Type</label>
            <select name="type" id="type" class="input">
              <option value="provider" {{ old('type')==='provider' ? 'selected' : '' }}>Provider (logo)</option>
              <option value="game" {{ old('type')==='game' ? 'selected' : '' }}>Game (thumbnail)</option>
            </select>
          </div>

          <div class="row" id="providerQuickRow">
            <label class="label">Provider (quick select)</label>
            <select id="providerQuick" class="input">
              <option value="">-- choose provider --</option>
              @foreach($providers as $p)
                <option value="{{ $p->id }}">[{{ $p->code }}] {{ $p->name }}</option>
              @endforeach
            </select>
            <div class="hint">Providers list is small; quick select is fastest.</div>
          </div>

          <div class="row">
            <label class="label">Search keyword</label>
            <input type="text" id="q" class="input" placeholder="type name or code (min 1 char)">
            <div class="hint" id="qHint">For games: type at least 1 char, it will show top 30 matches.</div>
          </div>

          <div class="row">
            <label class="label">Result</label>
            <select id="resultSelect" class="input" size="10"></select>
            <div class="hint">Click one result to select.</div>
          </div>

          <input type="hidden" name="target_id" id="target_id" value="{{ old('target_id') }}">

          <div class="row">
            <label class="label">Selected</label>
            <input type="text" id="selectedText" class="input" readonly placeholder="No selection yet">
          </div>

          <div class="row">
            <label class="label">Image</label>
            <input type="file" name="image" class="input" accept="image/*" required>
            <div class="hint">Max 5MB.</div>
          </div>

          <div class="two">
            <div class="row">
              <label class="label">Label (optional)</label>
              <input type="text" name="label" class="input" value="{{ old('label') }}" placeholder="logo / thumb / cover">
            </div>

            <div class="row">
              <label class="label">Sort order</label>
              <input type="number" name="sort_order" class="input" value="{{ old('sort_order', 0) }}" min="0">
            </div>
          </div>

          <div class="row">
            <label style="display:flex; gap:8px; align-items:center; opacity:.9; font-size:13px;">
              <input type="checkbox" name="is_primary" value="1" checked>
              Set as primary
            </label>
            <div class="hint">If checked, old primary image will be cleared for that game/provider.</div>
          </div>

          <button type="submit" class="btn">Upload & Save</button>
        </form>
      </div>

      <div class="right">
        <div class="card">
          <div style="font-weight:800; margin-bottom:8px;">Tips</div>
          <div class="hint">
            • Provider: use quick select, no need to search.<br>
            • Game: type at least 1 character, then pick from results.<br>
            • “Set as primary” will automatically un-primary existing ones.<br>
            • Sort order controls display order in image lists.
          </div>

          <div style="margin-top:14px; font-weight:800;">Paths</div>
          <div class="hint">
            Providers: <span class="mono">public/images/providers</span><br>
            Games: <span class="mono">public/images/games</span>
          </div>
        </div>

        <div class="card" style="margin-top:14px;">
          <div id="previewWrap" style="opacity:.85;">
            Select a provider/game to load existing images...
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  const typeEl = document.getElementById('type');
  const qEl = document.getElementById('q');
  const qHint = document.getElementById('qHint');
  const resultSelect = document.getElementById('resultSelect');
  const targetIdEl = document.getElementById('target_id');
  const selectedText = document.getElementById('selectedText');

  const providerQuickRow = document.getElementById('providerQuickRow');
  const providerQuick = document.getElementById('providerQuick');
  const previewWrap = document.getElementById('previewWrap');

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const URL_SEARCH = @json(route('admin.dbox.images.upload.search'));
  const URL_PREVIEW = @json(route('admin.dbox.images.upload.preview'));

  const URL_GAME_PRIMARY = @json(route('admin.dbox.games.images.primary', ['img' => '__IMG__']));
  const URL_GAME_DELETE  = @json(route('admin.dbox.games.images.destroy', ['img' => '__IMG__']));
  const URL_PROV_PRIMARY = @json(route('admin.dbox.providers.images.primary', ['img' => '__IMG__']));
  const URL_PROV_DELETE  = @json(route('admin.dbox.providers.images.destroy', ['img' => '__IMG__']));

  let timer = null;

  function clearResults() {
    resultSelect.innerHTML = '';
    targetIdEl.value = '';
    selectedText.value = '';
  }

  function setSelected(id, text) {
    targetIdEl.value = String(id || '');
    selectedText.value = text || '';
    refreshPreview();
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

  async function runSearch() {
    const type = typeEl.value;
    const q = (qEl.value || '').trim();

    if (type === 'provider' && providerQuick.value) {
      setSelected(providerQuick.value, providerQuick.options[providerQuick.selectedIndex].text);
      return;
    }

    if (type === 'game' && q.length < 1) {
      clearResults();
      return;
    }

    const url = new URL(URL_SEARCH, window.location.origin);
    url.searchParams.set('type', type);
    url.searchParams.set('q', q);

    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
    const data = await res.json().catch(() => ({ items: [] }));
    const items = Array.isArray(data.items) ? data.items : [];

    resultSelect.innerHTML = '';
    items.forEach(it => {
      const opt = document.createElement('option');
      opt.value = it.id;
      opt.textContent = `[${it.code}] ${it.name}`;
      resultSelect.appendChild(opt);
    });

    if (items.length) {
      resultSelect.selectedIndex = 0;
      setSelected(items[0].id, `[${items[0].code}] ${items[0].name}`);
    } else {
      setSelected('', '');
    }
  }

  function syncTypeUI() {
    clearResults();
    qEl.value = '';
    providerQuick.value = '';
    previewWrap.innerHTML = 'Select a provider/game to load existing images...';

    if (typeEl.value === 'provider') {
      providerQuickRow.style.display = '';
      qEl.placeholder = 'optional: search provider name/code';
      qHint.textContent = 'Providers: quick select is fastest (search optional).';
      runSearch();
    } else {
      providerQuickRow.style.display = 'none';
      qEl.placeholder = 'type game name or code (min 1 char)';
      qHint.textContent = 'For games: type at least 1 char, it will show top 30 matches.';
    }
  }

  async function refreshPreview(){
    if (!targetIdEl.value) {
      previewWrap.innerHTML = 'Select a provider/game to load existing images...';
      return;
    }

    const url = new URL(URL_PREVIEW, window.location.origin);
    url.searchParams.set('type', typeEl.value);
    url.searchParams.set('target_id', targetIdEl.value);

    previewWrap.innerHTML = 'Loading images...';

    const r = await fetchJson(url.toString());
    if (!r.ok || !r.data?.ok || !r.data?.html) {
      previewWrap.innerHTML = `Failed to load images (HTTP ${r.status}).`;
      return;
    }

    previewWrap.innerHTML = r.data.html;
    bindPreviewActions();
  }

  function buildActionUrl(type, action, imgId){
    const id = String(imgId);
    if (type === 'game') {
      return action === 'primary'
        ? URL_GAME_PRIMARY.replace('__IMG__', id)
        : URL_GAME_DELETE.replace('__IMG__', id);
    }
    return action === 'primary'
      ? URL_PROV_PRIMARY.replace('__IMG__', id)
      : URL_PROV_DELETE.replace('__IMG__', id);
  }

  async function doImageAction(btn){
    const action = btn.getAttribute('data-img-action'); // primary | delete
    const type = btn.getAttribute('data-img-type');     // game | provider
    const imgId = btn.getAttribute('data-img-id');

    if (!action || !type || !imgId) return;

    if (action === 'delete') {
      if (!confirm('Delete this image?')) return;
    }

    const url = buildActionUrl(type, action, imgId);

    const r = await fetchJson(url, {
      method: action === 'primary' ? 'POST' : 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
    });

    if (!r.ok) {
      alert(`Action failed (HTTP ${r.status}).`);
      return;
    }

    await refreshPreview();
  }

  function bindPreviewActions(){
    previewWrap.querySelectorAll('[data-img-action]').forEach(btn => {
      btn.onclick = () => doImageAction(btn);
    });
  }

  typeEl.addEventListener('change', syncTypeUI);

  providerQuick.addEventListener('change', () => {
    if (typeEl.value === 'provider' && providerQuick.value) {
      setSelected(providerQuick.value, providerQuick.options[providerQuick.selectedIndex].text);
    } else {
      setSelected('', '');
    }
  });

  qEl.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
  });

  resultSelect.addEventListener('change', () => {
    const opt = resultSelect.options[resultSelect.selectedIndex];
    if (!opt) return;
    setSelected(opt.value, opt.textContent);
  });

  document.getElementById('uploadForm').addEventListener('submit', (e) => {
    if (!targetIdEl.value) {
      e.preventDefault();
      alert('Please select a game/provider first.');
    }
  });

  syncTypeUI();
})();
</script>
@endsection
