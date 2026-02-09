<table>
  <thead>
    <tr>
      <th class="col-id">ID</th>
      <th class="col-code">Code</th>
      <th class="col-name">Name</th>
      <th class="col-active">Active</th>
      <th class="col-games">Games</th>
      <th class="col-sort">Sort</th>
      <th class="col-sync">Last Synced</th>
      <th class="col-actions">Actions</th>
    </tr>
  </thead>

  <tbody>
    @forelse ($providers as $p)
      <tr>
        <td class="col-id">{{ $p->id }}</td>

        <td class="col-code">
          <span class="clip mono" title="{{ $p->code }}">{{ $p->code }}</span>
          <span class="sub clip" title="sort_order: {{ $p->sort_order ?? 0 }}">sort: {{ $p->sort_order ?? 0 }}</span>
        </td>

        <td class="col-name">
          <span class="clip" title="{{ $p->name }}">{{ $p->name }}</span>
          <span class="sub clip" title="Primary image">
            @if ($p->primaryImage)
              primary image: <span class="mono">{{ $p->primaryImage->path }}</span>
            @else
              no image
            @endif
          </span>
        </td>

        <td class="col-active">
          <span class="pill {{ $p->is_active ? 'on' : 'off' }}">
            <span class="dot"></span>
            <span>{{ $p->is_active ? 'active' : 'inactive' }}</span>
          </span>
          <div class="sub">
            <button class="btn" type="button" data-toggle-provider data-provider-id="{{ $p->id }}">
              Toggle
            </button>
          </div>
        </td>

        <td class="col-games">
          <span class="clip">{{ $p->games_count ?? 0 }} total</span>
          <span class="sub clip">{{ $p->active_games_count ?? 0 }} active</span>
        </td>

        <td class="col-sort">
          <input class="input inline-num" type="number" min="0" step="1"
                 value="{{ $p->sort_order ?? 0 }}"
                 data-sort-provider data-provider-id="{{ $p->id }}" />
        </td>

        <td class="col-sync">
          <span class="clip" title="{{ $p->last_synced_at }}">{{ $p->last_synced_at ?? '-' }}</span>
          <span class="sub clip">updated: {{ $p->updated_at ?? '-' }}</span>
        </td>

        <td class="col-actions">
          <button class="btn" type="button" data-view-provider data-provider-id="{{ $p->id }}">View</button>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="8" style="padding:14px; opacity:.8;">No providers found.</td>
      </tr>
    @endforelse
  </tbody>
</table>
