<div id="lmhTxModal">

  <div style="display:flex; gap:14px; flex-wrap:wrap;">
    <div class="card" style="flex:1; min-width:360px;">
      <div style="font-weight:800; margin-bottom:8px;">Transaction Info</div>

      <div style="display:grid; grid-template-columns: 170px 1fr; gap:6px 10px; font-size:13px;">
        <div style="opacity:.7;">TX ID</div><div><strong>#{{ $tx->id }}</strong></div>

        <div style="opacity:.7;">Direction</div><div>{{ $tx->direction ?? '-' }}</div>
        <div style="opacity:.7;">Status</div><div>{{ $tx->status }}</div>

        <div style="opacity:.7;">Wallet</div><div>{{ $tx->wallet_type ?? '-' }} <span style="opacity:.7;">(wallet_id {{ $tx->wallet_id ?? '-' }})</span></div>

        <div style="opacity:.7;">Amount</div><div>{{ $tx->amount ?? '0' }}</div>
        <div style="opacity:.7;">Before</div><div>{{ $tx->balance_before ?? '0' }}</div>
        <div style="opacity:.7;">After</div><div>{{ $tx->balance_after ?? '0' }}</div>

        <div style="opacity:.7;">Occurred</div><div>{{ $tx->occurred_at ?? '-' }}</div>
        <div style="opacity:.7;">Created</div><div>{{ $tx->created_at ?? '-' }}</div>
        <div style="opacity:.7;">Updated</div><div>{{ $tx->updated_at ?? '-' }}</div>
      </div>
    </div>

    <div class="card" style="width:380px; min-width:320px;">
      <div style="font-weight:800; margin-bottom:8px;">User</div>

      <div style="display:grid; grid-template-columns: 120px 1fr; gap:6px 10px; font-size:13px;">
        <div style="opacity:.7;">User ID</div><div><strong>#{{ $tx->user_id }}</strong></div>
        <div style="opacity:.7;">Username</div><div>{{ $tx->username ?? '-' }}</div>
        <div style="opacity:.7;">Email</div><div><span class="clip" title="{{ $tx->email }}">{{ $tx->email ?? '-' }}</span></div>
        <div style="opacity:.7;">Country</div><div>{{ $tx->user_country ?? '-' }}</div>
        <div style="opacity:.7;">Currency</div><div>{{ $tx->user_currency ?? '-' }}</div>
      </div>

      <div style="margin-top:12px; opacity:.75; font-size:12px;">
        Tip: Filter by User ID + wallet type to audit balance changes quickly.
      </div>
    </div>
  </div>

  <div style="display:flex; gap:14px; flex-wrap:wrap; margin-top:14px;">
    <div class="card" style="flex:1; min-width:360px;">
      <div style="font-weight:800; margin-bottom:8px;">Provider Linkage</div>

      <div style="display:grid; grid-template-columns: 170px 1fr; gap:8px 10px; font-size:13px;">
        <div style="opacity:.7;">Provider</div>
        <div><input class="input" data-tx-field="provider" value="{{ $tx->provider ?? '' }}" /></div>

        <div style="opacity:.7;">Game Code</div>
        <div><input class="input" data-tx-field="game_code" value="{{ $tx->game_code ?? '' }}" /></div>

        <div style="opacity:.7;">Round Ref</div>
        <div><input class="input" data-tx-field="round_ref" value="{{ $tx->round_ref ?? '' }}" /></div>

        <div style="opacity:.7;">Bet ID</div>
        <div><input class="input" data-tx-field="bet_id" value="{{ $tx->bet_id ?? '' }}" /></div>
      </div>
    </div>

    <div class="card" style="flex:1; min-width:360px;">
      <div style="font-weight:800; margin-bottom:8px;">Identifiers</div>

      <div style="display:grid; grid-template-columns: 170px 1fr; gap:8px 10px; font-size:13px;">
        <div style="opacity:.7;">Reference</div>
        <div><input class="input" data-tx-field="reference" value="{{ $tx->reference ?? '' }}" /></div>

        <div style="opacity:.7;">External ID</div>
        <div><input class="input" data-tx-field="external_id" value="{{ $tx->external_id ?? '' }}" /></div>

        <div style="opacity:.7;">TX Hash</div>
        <div><input class="input" data-tx-field="tx_hash" value="{{ $tx->tx_hash ?? '' }}" /></div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:14px;">
    <div style="font-weight:800; margin-bottom:8px;">Admin Editable Fields</div>

    <div style="display:grid; grid-template-columns: 170px 1fr; gap:8px 10px; font-size:13px;">
      <div style="opacity:.7;">Status</div>
      <div>
        <select class="input" data-tx-field="status">
          <option value="0" @selected((int)$tx->status===0)>pending</option>
          <option value="1" @selected((int)$tx->status===1)>completed</option>
          <option value="2" @selected((int)$tx->status===2)>reversed</option>
          <option value="3" @selected((int)$tx->status===3)>failed</option>
          <option value="4" @selected((int)$tx->status===4)>cancelled</option>
        </select>
      </div>

      <div style="opacity:.7;">Title</div>
      <div><input class="input" data-tx-field="title" value="{{ $tx->title ?? '' }}" /></div>

      <div style="opacity:.7;">Description</div>
      <div><input class="input" data-tx-field="description" value="{{ $tx->description ?? '' }}" /></div>
    </div>

    <div style="margin-top:12px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
      <button class="btn" type="button" data-tx-update data-tx-id="{{ $tx->id }}">Save Changes</button>
      <button class="btn btn-danger" type="button" data-tx-reverse data-tx-id="{{ $tx->id }}">Reverse TX</button>
    </div>

    <div data-tx-msg style="margin-top:10px; opacity:.9;"></div>
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
