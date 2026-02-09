<table>
  <thead>
    <tr>
      <th class="col-id">ID</th>
      <th class="col-provider">Provider</th>
      <th class="col-code">Code</th>
      <th class="col-name">Name</th>
      <th class="col-hot">Hot</th>
      <th class="col-active">Active</th>
      <th class="col-curs">Currencies</th>
      <th class="col-sort">Sort</th>
      <th class="col-seen">Last Seen</th>
      <th class="col-actions">Actions</th>
    </tr>
  </thead>

  <tbody>
    @forelse ($games as $g)
      <tr>
        <td class="col-id">{{ $g->id }}</td>

        <td class="col-provider">
          <span class="clip" title="{{ $g->provider?->name }}">{{ $g->provider?->name ?? '-' }}</span>
          <span class="sub clip mono">{{ $g->provider?->code ?? '-' }}</span>
        </td>

        <td class="col-code">
          <span class="clip mono" title="{{ $g->code }}">{{ $g->code }}</span>
          <span class="sub clip">sort: {{ $g->sort_order ?? 0 }}</span>
        </td>

        <td class="col-name">
          <span class="clip" title="{{ $g->name }}">{{ $g->name }}</span>
          <span class="sub clip">
            @if ($g->primaryImage)
              primary img: <span class="mono">{{ $g->primaryImage->path }}</span>
            @else
              no image
            @endif
          </span>
        </td>

        <td class="col-hot">
          <span class="pill {{ $g->supports_launch ? 'hot' : 'off' }}">
            <span class="dot"></span>
            <span>{{ $g->supports_launch ? 'hot' : 'no' }}</span>
          </span>
        </td>

        <td class="col-active">
          <span class="pill {{ $g->is_active ? 'on' : 'off' }}">
            <span class="dot"></span>
            <span>{{ $g->is_active ? 'active' : 'inactive' }}</span>
          </span>
          <div class="sub">
            <button class="btn" type="button" data-toggle-game data-game-id="{{ $g->id }}">Toggle</button>
          </div>
        </td>

        <td class="col-curs">
          <span class="clip">{{ $g->active_currency_count ?? 0 }} active</span>
          <span class="sub clip">filterable</span>
        </td>

        <td class="col-sort">
          <input class="input inline-num" type="number" min="0" step="1"
                 value="{{ $g->sort_order ?? 0 }}"
                 data-sort-game data-game-id="{{ $g->id }}" />
        </td>

        <td class="col-seen">
          <span class="clip" title="{{ $g->last_seen_at }}">{{ $g->last_seen_at ?? '-' }}</span>
          <span class="sub clip">updated: {{ $g->updated_at ?? '-' }}</span>
        </td>

        <td class="col-actions">
          <button class="btn" type="button" data-view-game data-game-id="{{ $g->id }}">View</button>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="10" style="padding:14px; opacity:.8;">No games found.</td>
      </tr>
    @endforelse
  </tbody>
</table>
