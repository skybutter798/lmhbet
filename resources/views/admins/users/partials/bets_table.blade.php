<div class="tableWrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Game</th>
        <th class="right">Stake</th>
        <th class="right">Payout</th>
        <th>At</th>
      </tr>
    </thead>
    <tbody>
      @forelse($bets as $b)
        <tr>
          <td><strong>{{ $b->id }}</strong></td>
          <td>{{ $b->game ?? $b->game_code ?? $b->provider ?? '-' }}</td>
          <td class="right money">{{ isset($b->stake_amount) ? number_format((float)$b->stake_amount, 2) : '-' }}</td>
          <td class="right money">{{ isset($b->payout_amount) ? number_format((float)$b->payout_amount, 2) : '-' }}</td>
          <td class="muted">{{ $b->bet_at ?? $b->created_at ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" style="padding:12px; opacity:.8;">No bet records.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
