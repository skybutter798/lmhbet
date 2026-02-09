<div>
  <div class="card">
    <div style="font-weight:800; margin-bottom:8px;">Edit Provider</div>

    <form id="providerForm">
      @csrf

      <div style="display:grid; grid-template-columns: 180px 1fr; gap:8px 12px; font-size:13px;">
        <div style="opacity:.7;">ID</div><div><strong>#{{ $provider->id }}</strong></div>

        <div style="opacity:.7;">Code</div>
        <div>
          <input class="input" name="code" value="{{ $provider->code }}" />
          <div style="opacity:.75; font-size:12px; margin-top:4px;">If code is sync-managed, keep it unchanged.</div>
        </div>

        <div style="opacity:.7;">Name</div>
        <div><input class="input" name="name" value="{{ $provider->name }}" /></div>

        <div style="opacity:.7;">Active</div>
        <div>
          <select class="input" name="is_active">
            <option value="1" {{ $provider->is_active ? 'selected':'' }}>active</option>
            <option value="0" {{ !$provider->is_active ? 'selected':'' }}>inactive</option>
          </select>
        </div>

        <div style="opacity:.7;">Sort Order</div>
        <div><input class="input" type="number" min="0" step="1" name="sort_order" value="{{ $provider->sort_order ?? 0 }}" /></div>

        <div style="opacity:.7;">Games</div>
        <div>
          <strong>{{ $provider->games_count ?? 0 }}</strong> total,
          <strong>{{ $provider->active_games_count ?? 0 }}</strong> active
        </div>

        <div style="opacity:.7;">Last Synced</div><div>{{ $provider->last_synced_at ?? '-' }}</div>
        <div style="opacity:.7;">Updated</div><div>{{ $provider->updated_at ?? '-' }}</div>
      </div>

      <div style="display:flex; gap:10px; align-items:center; margin-top:14px;">
        <button class="btn" type="submit">Save</button>
        <div id="providerFormMsg" style="opacity:.85; font-size:13px;"></div>
      </div>
    </form>
  </div>

  <div class="card" style="margin-top:14px;">
    <div style="font-weight:800; margin-bottom:8px;">Images</div>

    <div style="opacity:.8; font-size:12px;">
      You already have image endpoints/controllers. If you move those routes under <span class="mono">admin.auth</span>,
      you can manage images here via your upload forms or dedicated screens.
    </div>

    <div style="margin-top:10px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        @forelse ($provider->images ?? [] as $img)
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
</div>
