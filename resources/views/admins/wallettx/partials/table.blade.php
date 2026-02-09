<table>
  <thead>
    <tr>
      <th class="col-id">ID</th>
      <th class="col-time">Time</th>
      <th class="col-user">User</th>
      <th class="col-wallet">Wallet</th>
      <th class="col-dir">Direction</th>
      <th class="col-money">Amount</th>
      <th class="col-money">Before</th>
      <th class="col-money">After</th>
      <th class="col-status">Status</th>
      <th class="col-title">Title</th>
      <th class="col-provider">Provider</th>
      <th class="col-game">Game</th>
      <th class="col-ref">Reference</th>
      <th class="col-round">Round</th>
      <th class="col-actions">Actions</th>
    </tr>
  </thead>

  <tbody>
    @forelse ($txs as $t)
      @php
        $dir = strtolower($t->direction ?? '');
        $dirClass = in_array($dir, ['credit','debit']) ? "dir-{$dir}" : '';

        $st = (string)($t->status ?? '');
        $stClass = "st-{$st}";
      @endphp

      <tr>
        <td class="col-id">{{ $t->id }}</td>

        <td class="col-time">
          <span class="clip" title="{{ $t->occurred_at ?? $t->created_at }}">
            {{ $t->occurred_at ?? $t->created_at ?? '-' }}
          </span>
          <span class="sub">
            <span class="clip mono" title="created_at">{{ $t->created_at ?? '-' }}</span>
          </span>
        </td>

        <td class="col-user">
          <div>
            <span class="clip" title="User #{{ $t->user_id }}">
              {{ $t->username ?? ('User#'.$t->user_id) }}
            </span>
          </div>
          <div class="sub">
            <span class="clip" title="{{ $t->email }}">{{ $t->email ?? '-' }}</span>
          </div>
        </td>

        <td class="col-wallet">
          <span class="clip mono" title="{{ $t->wallet_type }}">{{ $t->wallet_type ?? '-' }}</span>
          <span class="sub">
            <span class="clip mono" title="wallet_id">{{ $t->wallet_id ?? '-' }}</span>
          </span>
        </td>

        <td class="col-dir">
          <span class="pill {{ $dirClass }}">
            <span class="dot"></span>
            <span class="clip" title="{{ $t->direction }}">{{ $t->direction ?? '-' }}</span>
          </span>
        </td>

        <td class="col-money">{{ $t->amount ?? '0.00' }}</td>
        <td class="col-money">{{ $t->balance_before ?? '0.00' }}</td>
        <td class="col-money">{{ $t->balance_after ?? '0.00' }}</td>

        <td class="col-status">
          <span class="pill {{ $stClass }}">
            <span class="dot"></span>
            <span class="clip" title="{{ $t->status }}">
              @php
                $label = match((int)($t->status ?? -1)) {
                  0 => 'pending',
                  1 => 'completed',
                  2 => 'reversed',
                  3 => 'failed',
                  4 => 'cancelled',
                  default => (string)($t->status ?? '-'),
                };
              @endphp
              {{ $label }}
            </span>
          </span>
        </td>

        <td class="col-title">
          <span class="clip" title="{{ $t->title }}">{{ $t->title ?? '-' }}</span>
          <span class="sub">
            <span class="clip" title="{{ $t->description }}">{{ $t->description ?? '-' }}</span>
          </span>
        </td>

        <td class="col-provider">
          <span class="clip mono" title="{{ $t->provider }}">{{ $t->provider ?? '-' }}</span>
          <span class="sub"><span class="clip mono" title="external_id">{{ $t->external_id ?? '-' }}</span></span>
        </td>

        <td class="col-game">
          <span class="clip mono" title="{{ $t->game_code }}">{{ $t->game_code ?? '-' }}</span>
          <span class="sub"><span class="clip mono" title="bet_id">{{ $t->bet_id ?? '-' }}</span></span>
        </td>

        <td class="col-ref">
          <span class="clip mono" title="{{ $t->reference }}">{{ $t->reference ?? '-' }}</span>
          <span class="sub"><span class="clip mono" title="tx_hash">{{ $t->tx_hash ?? '-' }}</span></span>
        </td>

        <td class="col-round">
          <span class="clip mono" title="{{ $t->round_ref }}">{{ $t->round_ref ?? '-' }}</span>
        </td>

        <td class="col-actions">
          <button class="btn" type="button" data-view-tx data-tx-id="{{ $t->id }}">View</button>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="15" style="padding:14px; opacity:.8;">No transactions found.</td>
      </tr>
    @endforelse
  </tbody>
</table>
