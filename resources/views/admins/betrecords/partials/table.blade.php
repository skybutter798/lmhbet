{{-- resources/views/admins/betrecords/partials/table.blade.php --}}

<table>
  <thead>
    <tr>
      <th class="col-id">ID</th>
      <th class="col-betat">Bet At</th>
      <th class="col-user">User</th>
      <th class="col-provider">Provider</th>
      <th class="col-game">Game</th>
      <th class="col-betid">Bet ID</th>
      <th class="col-round">Round</th>
      <th class="col-cur">Cur</th>
      <th class="col-wallet">Wallet</th>
      <th class="col-money">Stake</th>
      <th class="col-money">Payout</th>
      <th class="col-money">Profit</th>
      <th class="col-status">Status</th>
      <th class="col-settled">Settled At</th>
      <th class="col-actions">Actions</th>
    </tr>
  </thead>

  <tbody>
    @forelse ($bets as $b)
      @php
        $st = strtolower($b->status ?? '');
        $stClass = in_array($st, ['open','settled','cancelled','void']) ? "st-{$st}" : '';
      @endphp

      <tr>
        <td class="col-id">{{ $b->id }}</td>

        <td class="col-betat">
          <span class="clip" title="{{ $b->bet_at }}">{{ $b->bet_at ?? '-' }}</span>
        </td>

        <td class="col-user">
          <div>
            <span class="clip" title="User #{{ $b->user_id }}">
              {{ $b->username ?? ('User#'.$b->user_id) }}
            </span>
          </div>
          <div class="sub">
            <span class="clip" title="{{ $b->email }}">{{ $b->email ?? '-' }}</span>
          </div>
        </td>

        <td class="col-provider">
          <div>
            <span class="clip" title="{{ $b->provider }}">{{ $b->provider ?? '-' }}</span>
          </div>
          <div class="sub">
            <span class="clip" title="{{ $b->provider_name }}">{{ $b->provider_name ?: '-' }}</span>
          </div>
        </td>

        <td class="col-game">
          <div>
            <span class="clip mono" title="{{ $b->game_code }}">{{ $b->game_code ?? '-' }}</span>
          </div>
          <div class="sub">
            <span class="clip" title="{{ $b->game_name }}">{{ $b->game_name ?: '-' }}</span>
          </div>
        </td>

        <td class="col-betid">
          <span class="clip mono" title="{{ $b->bet_id }}">{{ $b->bet_id ?? '-' }}</span>
        </td>

        <td class="col-round">
          <span class="clip mono" title="{{ $b->round_ref }}">{{ $b->round_ref ?? '-' }}</span>
        </td>

        <td class="col-cur">{{ $b->currency ?? '-' }}</td>
        <td class="col-wallet">{{ $b->wallet_type ?? '-' }}</td>

        <td class="col-money">{{ $b->stake2 ?? '0.00' }}</td>
        <td class="col-money">{{ $b->payout2 ?? '0.00' }}</td>
        <td class="col-money">{{ $b->profit2 ?? '0.00' }}</td>

        <td class="col-status">
          <span class="status-pill {{ $stClass }}">
            <span class="dot"></span>
            <span class="clip" title="{{ $b->status }}">{{ $b->status ?? '-' }}</span>
          </span>
        </td>

        <td class="col-settled">
          <span class="clip" title="{{ $b->settled_at }}">{{ $b->settled_at ?? '-' }}</span>
        </td>

        <td class="col-actions">
          <button class="btn" type="button" data-view-bet data-bet-id="{{ $b->id }}">View</button>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="15" style="padding:14px; opacity:.8;">No bet records found.</td>
      </tr>
    @endforelse
  </tbody>
</table>
