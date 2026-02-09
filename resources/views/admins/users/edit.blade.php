@extends('admins.layout')

@section('title', 'Edit User')

@section('body')
<style>
  #lmhEditUser * { box-sizing:border-box; }
  #lmhEditUser .muted { opacity:.78; }
  #lmhEditUser .hr { height:1px; background:#1f335c; opacity:.8; margin:12px 0; }

  #lmhEditUser .badge{
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 10px; border-radius:999px;
    border:1px solid #1f335c;
    font-size:12px; font-weight:900;
  }
  #lmhEditUser .badge.ok{ border-color:#2f6; color:#6dff8f; background:rgba(47,255,102,.08); }
  #lmhEditUser .badge.warn{ border-color:#ffd36d; color:#ffd36d; background:rgba(255,211,109,.08); }
  #lmhEditUser .badge.bad{ border-color:#ff8a8a; color:#ff8a8a; background:rgba(255,138,138,.08); }

  #lmhEditUser .pill{
    display:inline-block;
    padding:3px 10px;
    border-radius:999px;
    border:1px solid #1f335c;
    font-size:12px;
    opacity:.92;
  }

  #lmhEditUser .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
  @media (max-width: 980px){
    #lmhEditUser .grid2 { grid-template-columns: 1fr; }
  }

  #lmhEditUser .formGrid3 { display:grid; grid-template-columns: 220px 1fr 1fr; gap:12px; }
  @media (max-width: 980px){
    #lmhEditUser .formGrid3 { grid-template-columns: 1fr; }
  }

  #lmhEditUser .formGrid5 { display:grid; grid-template-columns: 140px 1fr 120px 120px 160px; gap:12px; }
  @media (max-width: 980px){
    #lmhEditUser .formGrid5 { grid-template-columns: 1fr; }
  }

  #lmhEditUser .miniCard{
    border:1px solid #1f335c;
    border-radius:14px;
    padding:14px;
    background: rgba(11,18,32,.55);
  }

  #lmhEditUser .kvs{
    display:grid;
    grid-template-columns: 180px 1fr;
    gap:8px 14px;
    line-height:1.6;
  }
  @media (max-width: 560px){
    #lmhEditUser .kvs { grid-template-columns: 1fr; }
  }
  #lmhEditUser .kvs .k{ opacity:.75; }
  #lmhEditUser .kvs .v strong{ font-weight:900; }

  #lmhEditUser .tableWrap { overflow:auto; border:1px solid #1f335c; border-radius:12px; }
  #lmhEditUser table { width:100%; border-collapse:collapse; min-width: 780px; }
  #lmhEditUser th{
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
  #lmhEditUser td{
    padding:10px;
    border-bottom:1px solid #162a52;
    white-space:nowrap;
  }
  #lmhEditUser tr:hover td { background: rgba(127,176,255,.06); }

  #lmhEditUser .money { font-variant-numeric: tabular-nums; font-weight:900; }
  #lmhEditUser .right { text-align:right; }

  /* tabs */
  #lmhEditUser .tabs {
    display:flex; gap:8px; flex-wrap:wrap;
    border:1px solid #1f335c;
    border-radius:12px;
    padding:8px;
    background: rgba(11,18,32,.55);
    margin-bottom:10px;
  }
  #lmhEditUser .tabBtn{
    appearance:none;
    border:1px solid #1f335c;
    background: transparent;
    color:#7fb0ff;
    padding:8px 12px;
    border-radius:10px;
    font-weight:900;
    cursor:pointer;
    font-size:13px;
  }
  #lmhEditUser .tabBtn.active{
    background: rgba(127,176,255,.12);
    border-color:#7fb0ff;
    color:#fff;
  }
  #lmhEditUser .tabPane{ display:none; }
  #lmhEditUser .tabPane.active{ display:block; }
</style>

<div class="app" id="lmhEditUser">
  @include('admins.partials.sidebar')

  <div class="content">

    <div class="topbar" style="align-items:flex-start;">
      <div style="display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:22px; font-weight:900;">
          Edit User: {{ $user->username }} <span class="muted" style="font-weight:800;">#{{ $user->id }}</span>
        </div>

        @php
          $isLocked = !is_null($user->locked_until) && \Carbon\Carbon::parse($user->locked_until)->gt(now());
          $tab = request('tab', 'tx');
        @endphp

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <span class="badge {{ $user->is_active ? 'ok' : 'bad' }}">{{ $user->is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
          <span class="badge {{ $user->banned_at ? 'bad' : 'ok' }}">{{ $user->banned_at ? 'BANNED' : 'NOT BANNED' }}</span>
          <span class="badge {{ $isLocked ? 'warn' : 'ok' }}">{{ $isLocked ? 'LOCKED' : 'NOT LOCKED' }}</span>
          <span class="badge">VIP: {{ optional($user->vipTier)->name ?? '-' }}</span>
          <span class="badge">KYC: {{ optional($user->kycProfile)->status ?? '-' }}</span>
        </div>
      </div>

      <div style="display:flex; gap:8px; align-items:center;">
        <a class="btn" href="{{ route('admin.users.index') }}">Back</a>
      </div>
    </div>

    @if(session('ok'))
      <div class="card" style="border-color:#2f6; margin-bottom:12px;">
        <strong>Saved.</strong> <span class="muted">{{ session('ok') }}</span>
      </div>
    @endif

    <div class="grid2">

      <div class="miniCard">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
          <div style="font-weight:900; font-size:16px;">User Details</div>
          <div class="muted" style="font-size:12px;">
            Created: <strong>{{ $user->created_at }}</strong>
          </div>
        </div>

        <div class="hr"></div>

        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
          @csrf

          <div class="formGrid3">
            <div>
              <label class="label">Username</label>
              <input class="input" name="username" value="{{ old('username', $user->username) }}" />
              @error('username') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div>
              <label class="label">Name</label>
              <input class="input" name="name" value="{{ old('name', $user->name) }}" />
            </div>

            <div>
              <label class="label">Email</label>
              <input class="input" name="email" value="{{ old('email', $user->email) }}" />
            </div>
          </div>

          <div style="margin-top:12px;" class="formGrid5">
            <div>
              <label class="label">Phone Country</label>
              <input class="input" name="phone_country" value="{{ old('phone_country', $user->phone_country) }}" />
            </div>

            <div>
              <label class="label">Phone</label>
              <input class="input" name="phone" value="{{ old('phone', $user->phone) }}" />
            </div>

            <div>
              <label class="label">Country</label>
              <input class="input" name="country" value="{{ old('country', $user->country) }}" />
            </div>

            <div>
              <label class="label">Currency</label>
              <input class="input" name="currency" value="{{ old('currency', $user->currency) }}" />
            </div>

            <div>
              <label class="label">VIP Tier ID</label>
              <input class="input" name="vip_tier_id" value="{{ old('vip_tier_id', $user->vip_tier_id) }}" />
            </div>
          </div>

          <div style="margin-top:14px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <label style="display:flex; gap:10px; align-items:center; margin:0;">
              <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
              <span style="font-weight:900;">Active</span>
            </label>

            <button class="btn" type="submit">Save Changes</button>
          </div>
        </form>
      </div>

      <div style="display:flex; flex-direction:column; gap:14px;">

        <div class="miniCard">
          <div style="font-weight:900; margin-bottom:10px;">Security / Status</div>

          <div class="kvs">
            <div class="k">Banned</div>
            <div class="v"><strong>{{ $user->banned_at ? 'YES' : 'NO' }}</strong></div>

            <div class="k">Ban Reason</div>
            <div class="v"><strong>{{ $user->ban_reason ?? '-' }}</strong></div>

            <div class="k">Locked Until</div>
            <div class="v"><strong>{{ $user->locked_until ?? '-' }}</strong></div>

            <div class="k">Failed Attempts</div>
            <div class="v"><strong>{{ $user->failed_login_attempts }}</strong></div>

            <div class="k">2FA Enabled</div>
            <div class="v"><strong>{{ $user->two_factor_enabled ? 'YES' : 'NO' }}</strong></div>

            <div class="k">Last Login</div>
            <div class="v">
              <strong>{{ $user->last_login_at ?? '-' }}</strong>
              <span class="muted">({{ $user->last_login_ip ?? '-' }})</span>
            </div>

            <div class="k">Referrer</div>
            <div class="v"><strong>{{ optional($user->referrer)->username ?? '-' }}</strong></div>
          </div>
        </div>

        <div class="miniCard">
          <div style="font-weight:900; margin-bottom:10px;">Bet Stats</div>
          <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <span class="pill">Total Bets: <strong>{{ $betStats->total_bets ?? 0 }}</strong></span>
            <span class="pill">Total Stake: <strong>{{ number_format((float)($betStats->total_stake ?? 0), 2) }}</strong></span>
            <span class="pill">Total Payout: <strong>{{ number_format((float)($betStats->total_payout ?? 0), 2) }}</strong></span>
            <span class="pill">Last Bet: <strong>{{ $betStats->last_bet_at ?? '-' }}</strong></span>
          </div>
        </div>

      </div>
    </div>

    <div class="miniCard" style="margin-top:14px;">
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
                <td class="muted">{{ $w->locked_until ?? '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="4" style="padding:12px; opacity:.8;">No wallets.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- SWITCH: Transactions <-> Bet Records --}}
    <div class="miniCard" style="margin-top:14px;">
      <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap;">
        <div>
          <div style="font-weight:900; font-size:16px;">Activity</div>
          <div class="muted" style="font-size:12px;">Switch between latest wallet transactions and bet records.</div>
        </div>
        <div class="muted" style="font-size:12px;">
          TX: <strong>{{ $transactions->total() }}</strong> |
          Bets: <strong>{{ $betRecords->total() }}</strong>
        </div>
      </div>

      <div class="hr"></div>

      <div class="tabs" id="activityTabs">
        <button type="button" class="tabBtn {{ $tab === 'tx' ? 'active' : '' }}" data-tab="tx">Latest Transactions</button>
        <button type="button" class="tabBtn {{ $tab === 'bets' ? 'active' : '' }}" data-tab="bets">Bet Records</button>
      </div>

      {{-- TX PANE --}}
      <div class="tabPane {{ $tab === 'tx' ? 'active' : '' }}" data-pane="tx">
        <div class="tableWrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Wallet</th>
                <th>Dir</th>
                <th class="right">Amount</th>
                <th>Status</th>
                <th>Title</th>
                <th>At</th>
              </tr>
            </thead>
            <tbody>
              @forelse($transactions as $t)
                @php
                  $status = strtolower((string)$t->status);
                  $cls = str_contains($status, 'fail') ? 'bad' : (str_contains($status, 'pend') ? 'warn' : 'ok');
                @endphp
                <tr>
                  <td><strong>{{ $t->id }}</strong></td>
                  <td>{{ $t->wallet_type ?? '-' }}</td>
                  <td>{{ $t->direction }}</td>
                  <td class="right money">{{ number_format((float)$t->amount, 2) }}</td>
                  <td><span class="badge {{ $cls }}">{{ strtoupper((string)$t->status) }}</span></td>
                  <td class="muted">{{ $t->title ?? '-' }}</td>
                  <td class="muted">{{ $t->created_at }}</td>
                </tr>
              @empty
                <tr><td colspan="7" style="padding:12px; opacity:.8;">No transactions.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div style="margin-top:12px;">
          {{ $transactions->appends(array_merge(request()->query(), ['tab' => 'tx']))->links('vendor.pagination.admin') }}
        </div>
      </div>

      {{-- BETS PANE --}}
      <div class="tabPane {{ $tab === 'bets' ? 'active' : '' }}" data-pane="bets">
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
              @forelse($betRecords as $b)
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

        <div style="margin-top:12px;">
          {{ $betRecords->appends(array_merge(request()->query(), ['tab' => 'bets']))->links('vendor.pagination.admin') }}
        </div>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  const root = document.getElementById('lmhEditUser');
  if (!root) return;

  const tabs = root.querySelectorAll('#activityTabs .tabBtn');
  const panes = root.querySelectorAll('.tabPane');

  function setTab(name) {
    tabs.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
    panes.forEach(p => p.classList.toggle('active', p.dataset.pane === name));

    // keep URL tab param (no reload)
    try {
      const u = new URL(window.location.href);
      u.searchParams.set('tab', name);
      history.replaceState(null, '', u.toString());
    } catch (e) {}
  }

  tabs.forEach(b => {
    b.addEventListener('click', () => setTab(b.dataset.tab));
  });
})();
</script>
@endsection
