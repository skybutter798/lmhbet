<table id="sortTable">
  <thead>
    <tr>
      <th style="width:56px;">#</th>
      <th style="width:80px;">ID</th>
      <th style="width:220px;">Code</th>
      <th style="width:420px;">Name</th>
      <th style="width:140px;">Sort</th>
      <th style="width:140px;">Hot</th>
      <th style="width:140px;">Active</th>
      <th style="width:180px;">Last Seen</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($games as $g)
      <tr data-game-id="{{ $g->id }}">
        <td class="dragcell">
          <button type="button" class="drag-handle" title="Drag to reorder" aria-label="Drag to reorder">☰</button>
        </td>
        <td>{{ $g->id }}</td>
        <td><span class="mono" title="{{ $g->code }}">{{ $g->code }}</span></td>
        <td title="{{ $g->name }}">{{ $g->name }}</td>
        <td class="mono">{{ $g->sort_order ?? 0 }}</td>
        <td>{{ $g->supports_launch ? 'hot' : '-' }}</td>
        <td>{{ $g->is_active ? 'active' : 'inactive' }}</td>
        <td>{{ $g->last_seen_at ?? '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="8" style="padding:12px; opacity:.8;">No games found.</td>
      </tr>
    @endforelse
  </tbody>
</table>

<div style="padding:10px; opacity:.8; font-size:12px;">
  Showing page {{ $games->currentPage() }} of {{ $games->lastPage() }} • {{ $games->total() }} total
</div>
