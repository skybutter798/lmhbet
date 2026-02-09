<div id="lmhBetModal">
  <div style="display:flex; gap:14px; flex-wrap:wrap;">
    <div class="card" style="flex:1; min-width:320px;">
      <div style="font-weight:800; margin-bottom:8px;">Bet Info</div>

      <div style="display:grid; grid-template-columns: 160px 1fr; gap:6px 10px; font-size:13px;">
        <div style="opacity:.7;">Bet ID</div><div><strong>#{{ $bet->id }}</strong></div>
        <div style="opacity:.7;">Status</div><div>{{ $bet->status ?? '-' }}</div>

        <div style="opacity:.7;">Bet At</div><div>{{ $bet->bet_at ?? '-' }}</div>
        <div style="opacity:.7;">Settled At</div><div>{{ $bet->settled_at ?? '-' }}</div>

        <div style="opacity:.7;">Provider</div>
        <div>{{ $bet->provider ?? '-' }} <span style="opacity:.75;">({{ $bet->provider_name ?: 'unknown' }})</span></div>

        <div style="opacity:.7;">Game</div>
        <div>{{ $bet->game_code ?? '-' }} <span style="opacity:.75;">({{ $bet->game_name ?: 'unknown' }})</span></div>

        <div style="opacity:.7;">Round Ref</div><div><span class="clip" title="{{ $bet->round_ref }}">{{ $bet->round_ref ?? '-' }}</span></div>
        <div style="opacity:.7;">Provider Bet ID</div><div><span class="clip" title="{{ $bet->bet_id }}">{{ $bet->bet_id ?? '-' }}</span></div>

        <div style="opacity:.7;">Currency</div><div>{{ $bet->currency ?? '-' }}</div>
        <div style="opacity:.7;">Wallet Type</div><div>{{ $bet->wallet_type ?? '-' }}</div>

        <div style="opacity:.7;">Stake</div><div>{{ $bet->stake_amount ?? '0' }}</div>
        <div style="opacity:.7;">Payout</div><div>{{ $bet->payout_amount ?? '0' }}</div>
        <div style="opacity:.7;">Profit</div><div>{{ $bet->profit_amount ?? '0' }}</div>

        <div style="opacity:.7;">Bet Ref</div><div><span class="clip" title="{{ $bet->bet_reference }}">{{ $bet->bet_reference ?? '-' }}</span></div>
        <div style="opacity:.7;">Settle Ref</div><div><span class="clip" title="{{ $bet->settle_reference }}">{{ $bet->settle_reference ?? '-' }}</span></div>

        <div style="opacity:.7;">Created</div><div>{{ $bet->created_at ?? '-' }}</div>
        <div style="opacity:.7;">Updated</div><div>{{ $bet->updated_at ?? '-' }}</div>
      </div>
    </div>

    <div class="card" style="width:360px; min-width:300px;">
      <div style="font-weight:800; margin-bottom:8px;">User</div>

      <div style="display:grid; grid-template-columns: 120px 1fr; gap:6px 10px; font-size:13px;">
        <div style="opacity:.7;">User ID</div><div><strong>#{{ $bet->user_id }}</strong></div>
        <div style="opacity:.7;">Username</div><div>{{ $bet->username ?? '-' }}</div>
        <div style="opacity:.7;">Email</div><div><span class="clip" title="{{ $bet->email }}">{{ $bet->email ?? '-' }}</span></div>
        <div style="opacity:.7;">Country</div><div>{{ $bet->user_country ?? '-' }}</div>
        <div style="opacity:.7;">Currency</div><div>{{ $bet->user_currency ?? '-' }}</div>
      </div>

      <div style="margin-top:12px; opacity:.75; font-size:12px;">
        Tip: Use filters (User ID / Search) to view all bets for this user.
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:14px;">
    <div style="font-weight:800; margin-bottom:8px;">Meta (raw)</div>
    @if (!empty($meta))
      <pre style="white-space:pre-wrap; font-size:12px; background:#0b1220; border:1px solid #1f335c; padding:10px; border-radius:12px; overflow:auto;">{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    @else
      <div style="opacity:.8;">No meta data.</div>
    @endif
  </div>
</div>
