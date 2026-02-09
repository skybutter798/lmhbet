<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>DBOX Image Upload</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    label { display:block; margin: 10px 0 6px; font-weight: bold; }
    select,input { width: 520px; max-width: 100%; padding: 8px; }
    .row { margin-bottom: 10px; }
    .ok { background:#e8ffe8; border:1px solid #9ae39a; padding:10px; margin-bottom:10px; }
    .err { background:#ffe8e8; border:1px solid #e39a9a; padding:10px; margin-bottom:10px; }
    button { padding:10px 16px; cursor:pointer; }
    .muted { color:#666; font-size: 12px; }
    .hint { font-size: 12px; color:#555; margin-top: 4px; }
  </style>
</head>
<body>
  <h2>DBOX Image Upload (Search)</h2>

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

  <form method="POST" action="{{ route('dbox.img.upload.store') }}" enctype="multipart/form-data" id="uploadForm">
    @csrf

    <div class="row">
      <label>Type</label>
      <select name="type" id="type">
        <option value="provider" {{ old('type')==='provider' ? 'selected' : '' }}>Provider (logo)</option>
        <option value="game" {{ old('type')==='game' ? 'selected' : '' }}>Game (thumbnail)</option>
      </select>
    </div>

    <div class="row" id="providerQuickRow">
      <label>Provider (quick select)</label>
      <select id="providerQuick">
        <option value="">-- choose provider --</option>
        @foreach($providers as $p)
          <option value="{{ $p->id }}">[{{ $p->code }}] {{ $p->name }}</option>
        @endforeach
      </select>
      <div class="hint">Providers list is small so no need search (but you still can).</div>
    </div>

    <div class="row">
      <label>Search keyword</label>
      <input type="text" id="q" placeholder="type name or code (min 1 char)">
      <div class="muted">For games: type anything, it will show top 30 matches.</div>
    </div>

    <div class="row">
      <label>Result</label>
      <select id="resultSelect" size="10" style="height:auto;"></select>
      <div class="hint">Click one result to select.</div>
    </div>

    <input type="hidden" name="target_id" id="target_id" value="{{ old('target_id') }}">

    <div class="row">
      <label>Selected</label>
      <input type="text" id="selectedText" readonly placeholder="No selection yet">
    </div>

    <div class="row">
      <label>Image</label>
      <input type="file" name="image" accept="image/*" required>
      <div class="muted">Max 5MB. Saved in public/images/providers or public/images/games</div>
    </div>

    <div class="row">
      <label>Label (optional)</label>
      <input type="text" name="label" value="{{ old('label') }}" placeholder="logo / thumb / cover">
    </div>

    <div class="row">
      <label>Sort order</label>
      <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
    </div>

    <div class="row">
      <label>
        <input type="checkbox" name="is_primary" value="1" checked>
        Set as primary
      </label>
    </div>

    <button type="submit">Upload & Save</button>
  </form>

  <script>
    const typeEl = document.getElementById('type');
    const qEl = document.getElementById('q');
    const resultSelect = document.getElementById('resultSelect');
    const targetIdEl = document.getElementById('target_id');
    const selectedText = document.getElementById('selectedText');

    const providerQuickRow = document.getElementById('providerQuickRow');
    const providerQuick = document.getElementById('providerQuick');

    let timer = null;

    function clearResults() {
      resultSelect.innerHTML = '';
      targetIdEl.value = '';
      selectedText.value = '';
    }

    function setSelected(id, text) {
      targetIdEl.value = String(id || '');
      selectedText.value = text || '';
    }

    async function runSearch() {
      const type = typeEl.value;
      const q = (qEl.value || '').trim();

      // For provider, allow quick select without search
      if (type === 'provider' && providerQuick.value) {
        setSelected(providerQuick.value, providerQuick.options[providerQuick.selectedIndex].text);
        return;
      }

      // For games, require at least 1 char to reduce load
      if (type === 'game' && q.length < 1) {
        clearResults();
        return;
      }

      const url = new URL("{{ route('dbox.img.upload.search') }}", window.location.origin);
      url.searchParams.set('type', type);
      url.searchParams.set('q', q);

      const res = await fetch(url.toString(), {
        headers: { 'Accept': 'application/json' }
      });

      const data = await res.json().catch(() => ({ items: [] }));
      const items = Array.isArray(data.items) ? data.items : [];

      resultSelect.innerHTML = '';
      items.forEach(it => {
        const opt = document.createElement('option');
        opt.value = it.id;
        opt.textContent = `[${it.code}] ${it.name}`;
        resultSelect.appendChild(opt);
      });

      // auto-select first
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

      if (typeEl.value === 'provider') {
        providerQuickRow.style.display = '';
        qEl.placeholder = 'optional: search provider name/code';
        // preload provider results
        runSearch();
      } else {
        providerQuickRow.style.display = 'none';
        qEl.placeholder = 'type game name or code (min 1 char)';
      }
    }

    typeEl.addEventListener('change', () => {
      syncTypeUI();
    });

    providerQuick.addEventListener('change', () => {
      if (typeEl.value === 'provider' && providerQuick.value) {
        setSelected(providerQuick.value, providerQuick.options[providerQuick.selectedIndex].text);
      } else {
        setSelected('', '');
      }
    });

    qEl.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(runSearch, 250); // debounce
    });

    resultSelect.addEventListener('change', () => {
      const opt = resultSelect.options[resultSelect.selectedIndex];
      if (!opt) return;
      setSelected(opt.value, opt.textContent);
    });

    // prevent submit without target_id
    document.getElementById('uploadForm').addEventListener('submit', (e) => {
      if (!targetIdEl.value) {
        e.preventDefault();
        alert('Please select a game/provider first.');
      }
    });

    syncTypeUI();
  </script>
</body>
</html>
