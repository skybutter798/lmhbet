<div class="tableWrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Wallet</th>
        <th>Dir</th>
        <th class="right">Amount</th>
        <th>Status</th>
        <th>Ref</th>
        <th>At</th>
      </tr>
    </thead>
    <tbody>
      @forelse($tx as $t)
        @php
          $status = strtolower((string)$t->status);
          $cls = str_contains($status, 'fail') ? 'bad' : (str_contains($status, 'pend') ? 'warn' : 'ok');
        @endphp
        <tr>
          <td><strong>{{ $t->id }}</strong></td>
          <td>{{ $t->wallet_type }}</td>
          <td>{{ $t->direction }}</td>
          <td class="right money">{{ number_format((float)$t->amount, 2) }}</td>
          <td><span class="badge {{ $cls }}">{{ strtoupper((string)$t->status) }}</span></td>
          <td class="muted">{{ $t->reference ?? '-' }}</td>
          <td class="muted">{{ $t->created_at }}</td>
        </tr>
      @empty
        <tr><td colspan="7" style="padding:12px; opacity:.8;">No transactions.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
