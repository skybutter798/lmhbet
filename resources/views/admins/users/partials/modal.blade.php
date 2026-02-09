<style>
  /* scope only inside modal */
  #lmhUserModal * { box-sizing: border-box; }

  #lmhUserModal .muted { opacity:.78; }
  #lmhUserModal .hr { height:1px; background:#1f335c; opacity:.8; margin:10px 0; }

  #lmhUserModal .grid2 { display:grid; grid-template-columns: 1.1fr .9fr; gap:12px; }
  @media (max-width: 900px) {
    #lmhUserModal .grid2 { grid-template-columns: 1fr; }
  }

  #lmhUserModal .kvs { display:grid; grid-template-columns: 160px 1fr; gap:6px 12px; line-height:1.6; }
  @media (max-width: 520px) {
    #lmhUserModal .kvs { grid-template-columns: 1fr; }
  }
  #lmhUserModal .kvs .k { opacity:.75; }
  #lmhUserModal .kvs .v strong { font-weight:800; }

  #lmhUserModal .badge {
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 10px; border-radius:999px;
    border:1px solid #1f335c;
    font-size:12px; font-weight:800;
  }
  #lmhUserModal .badge.ok { border-color:#2f6; color:#6dff8f; background:rgba(47,255,102,.08); }
  #lmhUserModal .badge.warn { border-color:#ffd36d; color:#ffd36d; background:rgba(255,211,109,.08); }
  #lmhUserModal .badge.bad { border-color:#ff8a8a; color:#ff8a8a; background:rgba(255,138,138,.08); }

  #lmhUserModal .tabs {
    position: sticky;
    top: 0;
    z-index: 5;
    background: rgba(11,18,32,.92);
    backdrop-filter: blur(6px);
    border:1px solid #1f335c;
    border-radius:12px;
    padding:8px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:12px;
  }
  #lmhUserModal .tabBtn {
    appearance:none;
    border:1px solid #1f335c;
    background: transparent;
    color:#7fb0ff;
    padding:8px 12px;
    border-radius:10px;
    font-weight:800;
    cursor:pointer;
    font-size:13px;
  }
  #lmhUserModal .tabBtn.active {
    background: rgba(127,176,255,.12);
    border-color:#7fb0ff;
    color:#fff;
  }

  #lmhUserModal .tabPane { display:none; }
  #lmhUserModal .tabPane.active { display:block; }

  #lmhUserModal .miniCard {
    border:1px solid #1f335c;
    border-radius:12px;
    padding:12px;
    background: rgba(11,18,32,.55);
  }

  #lmhUserModal .formRow { display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
  @media (max-width: 720px) {
    #lmhUserModal .formRow { grid-template-columns: 1fr; }
  }

  #lmhUserModal .tableWrap { overflow:auto; border:1px solid #1f335c; border-radius:12px; }
  #lmhUserModal table { width:100%; border-collapse:collapse; min-width: 720px; }
  #lmhUserModal th {
    position: sticky;
    top: 0;
    background: rgba(11,18,32,.92);
    border-bottom:1px solid #1f335c;
    text-align:left;
    padding:10px;
    font-size:12px;
    letter-spacing:.02em;
    text-transform: uppercase;
    opacity:.9;
  }
  #lmhUserModal td {
    padding:10px;
    border-bottom:1px solid #162a52;
    white-space:nowrap;
  }
  #lmhUserModal tr:hover td { background: rgba(127,176,255,.06); }

  #lmhUserModal .money { font-variant-numeric: tabular-nums; font-weight:800; }
  #lmhUserModal .right { text-align:right; }
  #lmhUserModal .pill {
    display:inline-block; padding:3px 8px; border-radius:999px;
    border:1px solid #1f335c; font-size:12px; opacity:.9;
  }
</style>

<div id="lmhUserModal">

  {{-- Top summary --}}
  <div class="miniCard" style="margin-bottom:12px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
      <div style="display:flex; flex-direction:column; gap:6px;">
        <div style="font-size:18px; font-weight:900;">
          {{ $user->username }} <span class="muted" style="font-weight:700;">#{{ $user->id }}</span>
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          @php
            $isLocked = !is_null($user->locked_until) && \Carbon\Carbon::parse($user->locked_until)->gt(now());
          @endphp

          <span class="badge {{ $user->is_active ? 'ok' : 'bad' }}">
            {{ $user->is_active ? 'ACTIVE' : 'INACTIVE' }}
          </span>

          <span class="badge {{ $user->banned_at ? 'bad' : 'ok' }}">
            {{ $user->banned_at ? 'BANNED' : 'NOT BANNED' }}
          </span>

          <span class="badge {{ $isLocked ? 'warn' : 'ok' }}">
            {{ $isLocked ? 'LOCKED' : 'NOT LOCKED' }}
          </span>

          <span class="badge">
            VIP: {{ optional($user->vipTier)->name ?? '-' }}
          </span>

          <span class="badge">
            KYC: {{ optional($user->kycProfile)->status ?? '-' }}
          </span>
        </div>
      </div>

      <div class="muted" style="font-size:12px;">
        Created: <strong>{{ $user->created_at }}</strong><br>
        Last Bet: <strong>{{ $betStats->last_bet_at ?? '-' }}</strong>
      </div>
    </div>
  </div>

  {{-- Tabs --}}
  <div class="tabs">
    <button type="button" class="tabBtn active" data-tab="overview">Overview</button>
    <button type="button" class="tabBtn" data-tab="wallets">Wallets</button>
    <button type="button" class="tabBtn" data-tab="tx">Transactions</button>
    <button type="button" class="tabBtn" data-tab="bets">Bets</button>
  </div>

  {{-- OVERVIEW --}}
  <div class="tabPane active" data-pane="overview">
    <div class="grid2">

      <div class="miniCard">
        <div style="font-weight:900; margin-bottom:8px;">Profile</div>

        <div class="kvs">
          <div class="k">Email</div>
          <div class="v"><strong>{{ $user->email ?? '-' }}</strong></div>

          <div class="k">Phone</div>
          <div class="v"><strong>{{ trim(($user->phone_country ?? '').' '.($user->phone ?? '')) ?: '-' }}</strong></div>

          <div class="k">Country / Currency</div>
          <div class="v"><strong>{{ $user->country ?? '-' }} / {{ $user->currency ?? '-' }}</strong></div>

          <div class="k">Referrer</div>
          <div class="v"><strong>{{ optional($user->referrer)->username ?? '-' }}</strong></div>

          <div class="k">Ban Reason</div>
          <div class="v"><strong>{{ $user->ban_reason ?? '-' }}</strong></div>

          <div class="k">Locked Until</div>
          <div class="v"><strong>{{ $user->locked_until ?? '-' }}</strong></div>
        </div>

        <div class="hr"></div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <span class="pill">Failed Attempts: <strong>{{ $user->failed_login_attempts ?? 0 }}</strong></span>
          <span class="pill">2FA: <strong>{{ ($user->two_factor_enabled ?? false) ? 'YES' : 'NO' }}</strong></span>
          <span class="pill">Last Login: <strong>{{ $user->last_login_at ?? '-' }}</strong></span>
        </div>
      </div>

      <div class="miniCard">
        <div style="font-weight:900; margin-bottom:8px;">Wallet Adjust</div>

        <form id="walletAdjustForm" method="POST" action="{{ route('admin.users.walletAdjust', $user->id) }}">
          @csrf
        
          <label class="label">Wallet Type</label>
          <select class="input" name="wallet_type" required>
            <option value="main">main</option>
            <option value="chips">chips</option>
            <option value="bonus">bonus</option>
            <!-- <option value="promote">promote</option>
            <option value="extra">extra</option> -->
          </select>
        
          <label class="label">Direction</label>
          <select class="input" name="direction" required>
            <option value="credit">credit</option>
            <option value="debit">debit</option>
          </select>
        
          <label class="label">Amount</label>
          <input class="input" name="amount" inputmode="decimal" placeholder="e.g. 10.5" required />
        
          <label class="label">Title</label>
          <input class="input" name="title" placeholder="Admin adjustment" />
        
          <label class="label">Description</label>
          <input class="input" name="description" placeholder="Reason/notes" />
        
          <button class="btn" type="submit" style="margin-top:12px; width:100%;">Submit</button>
        </form>


        <div class="hr"></div>

        <div style="font-weight:900; margin-bottom:8px;">Bet Summary</div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
          <span class="pill">Total Bets: <strong>{{ $betStats->total_bets ?? 0 }}</strong></span>
          <span class="pill">Total Stake: <strong>{{ number_format((float)($betStats->total_stake ?? 0), 2) }}</strong></span>
          <span class="pill">Total Payout: <strong>{{ number_format((float)($betStats->total_payout ?? 0), 2) }}</strong></span>
        </div>
      </div>

    </div>
  </div>

  {{-- WALLETS --}}
  <div class="tabPane" data-pane="wallets">
    <div class="miniCard">
      <div style="font-weight:900; margin-bottom:10px;">Wallets</div>
      <div class="tableWrap">
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th class="right">Balance</th>
              <th>Status</th>
              <th>Locked</th>
            </tr>
          </thead>
          <tbody>
            @forelse($wallets as $w)
              <tr>
                <td><strong>{{ $w->type }}</strong></td>
                <td class="right money">{{ number_format((float)$w->balance, 2) }}</td>
                <td>{{ $w->status }}</td>
                <td>{{ $w->locked_until ?? '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="4" style="padding:12px; opacity:.8;">No wallets.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- TRANSACTIONS --}}
  <div class="tabPane" data-pane="tx">
    <div class="miniCard">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
        <div style="font-weight:900;">Wallet Transactions</div>
        <div class="muted" style="font-size:12px;">25 / page</div>
      </div>

      <div id="txWrap" style="margin-top:10px;">
        @include('admins.users.partials.tx_table', ['tx' => $tx, 'user' => $user])
      </div>

      <div id="txPagination" style="margin-top:10px;">
        {!! $tx->links('vendor.pagination.admin') !!}
      </div>
    </div>
  </div>

  {{-- BETS --}}
  <div class="tabPane" data-pane="bets">
    <div class="miniCard">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
        <div style="font-weight:900;">Bet Records</div>
        <div class="muted" style="font-size:12px;">25 / page</div>
      </div>

      <div id="betsWrap" style="margin-top:10px;">
        @include('admins.users.partials.bets_table', ['bets' => $bets, 'user' => $user])
      </div>

      <div id="betsPagination" style="margin-top:10px;">
        {!! $bets->links('vendor.pagination.admin') !!}
      </div>
    </div>
  </div>

</div>

<script>
(function () {
  // tab switching inside modal
  const root = document.getElementById('lmhUserModal');
  if (!root) return;

  const btns = root.querySelectorAll('.tabBtn');
  const panes = root.querySelectorAll('.tabPane');

  function activate(name) {
    btns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
    panes.forEach(p => p.classList.toggle('active', p.dataset.pane === name));
  }

  btns.forEach(b => {
    b.addEventListener('click', () => activate(b.dataset.tab));
  });
})();
</script>
