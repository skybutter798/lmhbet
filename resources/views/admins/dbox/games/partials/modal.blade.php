@php
  $activeSet = collect($game->currencies ?? [])
    ->filter(fn($x) => (bool) $x->is_active)
    ->pluck('currency')
    ->map(fn($c) => strtoupper(trim((string)$c)))
    ->unique()
    ->values();
@endphp

<div>
  <div class="card">
    <div style="font-weight:800; margin-bottom:8px;">Edit Game</div>

    <form id="gameForm">
      @csrf

      <div style="display:grid; grid-template-columns: 180px 1fr; gap:8px 12px; font-size:13px;">
        <div style="opacity:.7;">ID</div><div><strong>#{{ $game->id }}</strong></div>

        <div style="opacity:.7;">Provider</div>
        <div>
          <select class="input" name="provider_id">
            @foreach ($providers as $p)
              <option value="{{ $p->id }}" {{ (string)$game->provider_id === (string)$p->id ? 'selected':'' }}>
                {{ $p->name }} ({{ $p->code }})
              </option>
            @endforeach
          </select>
        </div>

        <div style="opacity:.7;">Code</div>
        <div><input class="input" name="code" value="{{ $game->code }}" /></div>

        <div style="opacity:.7;">Name</div>
        <div><input class="input" name="name" value="{{ $game->name }}" /></div>

        <div style="opacity:.7;">Hot (supports_launch)</div>
        <div>
          <select class="input" name="supports_launch">
            <option value="1" {{ $game->supports_launch ? 'selected':'' }}>hot</option>
            <option value="0" {{ !$game->supports_launch ? 'selected':'' }}>no</option>
          </select>
        </div>

        <div style="opacity:.7;">Active</div>
        <div>
          <select class="input" name="is_active">
            <option value="1" {{ $game->is_active ? 'selected':'' }}>active</option>
            <option value="0" {{ !$game->is_active ? 'selected':'' }}>inactive</option>
          </select>
        </div>

        <div style="opacity:.7;">Sort Order</div>
        <div><input class="input" type="number" min="0" step="1" name="sort_order" value="{{ $game->sort_order ?? 0 }}" /></div>

        <div style="opacity:.7;">Product Group</div>
        <div><input class="input" name="product_group" value="{{ $game->product_group ?? '' }}" /></div>

        <div style="opacity:.7;">Sub Product Group</div>
        <div><input class="input" name="sub_product_group" value="{{ $game->sub_product_group ?? '' }}" /></div>

        <div style="opacity:.7;">Last Seen</div><div>{{ $game->last_seen_at ?? '-' }}</div>
        <div style="opacity:.7;">Updated</div><div>{{ $game->updated_at ?? '-' }}</div>
      </div>

      <div style="display:flex; gap:10px; align-items:center; margin-top:14px;">
        <button class="btn" type="submit">Save</button>
        <div id="gameFormMsg" style="opacity:.85; font-size:13px;"></div>
      </div>
    </form>
  </div>

  <div class="card" style="margin-top:14px;">
    <div style="font-weight:800; margin-bottom:8px;">Currencies</div>

    <form id="currencyForm">
      @csrf
      <div style="display:flex; flex-wrap:wrap; gap:10px;">
        @foreach ($allCurrencies as $c)
          <label style="display:flex; align-items:center; gap:8px; font-size:13px; opacity:.95;">
            <input type="checkbox" name="active_currencies[]" value="{{ $c }}" {{ $activeSet->contains($c) ? 'checked':'' }}>
            {{ $c }}
          </label>
        @endforeach
      </div>

      <div style="display:flex; gap:10px; align-items:center; margin-top:12px;">
        <button class="btn" type="submit">Update Currencies</button>
        <div id="currencyFormMsg" style="opacity:.85; font-size:13px;"></div>
      </div>
    </form>

    <div style="opacity:.8; font-size:12px; margin-top:10px;">
      Note: Public game listing uses currency + is_active checks; this controls that.
    </div>
  </div>

  <div class="card" style="margin-top:14px;">
    <div style="font-weight:800; margin-bottom:8px;">Images</div>
    <div style="opacity:.8; font-size:12px;">
      Same note as providers: keep your existing image controllers, but ensure routes are protected by <span class="mono">admin.auth</span>.
    </div>

    <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
      @forelse ($game->images ?? [] as $img)
        <div class="card" style="width:260px;">
          <div style="font-size:12px; opacity:.8;">
            {{ $img->is_primary ? 'PRIMARY' : 'secondary' }} • {{ $img->label ?? '-' }}
          </div>
          <div style="margin-top:8px; font-size:12px;">
            <div class="mono">{{ $img->path }}</div>
          </div>
        </div>
      @empty
        <div style="opacity:.8;">No images.</div>
      @endforelse
    </div>
  </div>
</div>
