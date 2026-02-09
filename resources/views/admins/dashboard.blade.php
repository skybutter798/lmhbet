{{-- /home/lmh/app/resources/views/admins/dashboard.blade.php --}}
@extends('admins.layout-dashboard')

@section('title', 'Admin Dashboard')

@section('body')
  @php
    $nf0 = fn($n) => number_format((float)$n, 0, '.', ',');
    $nf2 = fn($n) => number_format((float)$n, 2, '.', ',');

    $today = now()->toDateString();

    $hrefBetsToday   = route('admin.betrecords.index').'?from='.$today.'&to='.$today;
    $hrefBetsAll     = route('admin.betrecords.index');

    $hrefProfitToday = route('admin.betrecords.index').'?only_profit=1&from='.$today.'&to='.$today;
    $hrefProfitAll   = route('admin.betrecords.index').'?only_profit=1';

    $hrefWalletToday = route('admin.wallettx.index').'?occurred_from='.$today.'&occurred_to='.$today;
    $hrefWalletAll   = route('admin.wallettx.index');

    $hrefTopGameToday = route('admin.betrecords.index')
      .'?game='.urlencode((string)($statsToday['top_game_game_code'] ?? ''))
      .'&provider='.urlencode((string)($statsToday['top_game_provider'] ?? ''))
      .'&from='.$today.'&to='.$today;

    $hrefTopGameAll = route('admin.betrecords.index')
      .'?game='.urlencode((string)($statsAll['top_game_game_code'] ?? ''))
      .'&provider='.urlencode((string)($statsAll['top_game_provider'] ?? ''));
  @endphp

  <div class="app">
    @include('admins.partials.sidebar')

    <div class="content">
      <div class="orb o1"></div>
      <div class="orb o2"></div>
      <div class="orb o3"></div>

      <div class="topbar reveal">
        <div class="tb-left">
          <div class="tb-title">
            <span>Dashboard</span>
            <span style="font-size:12px; font-weight:900; padding:6px 10px; border-radius:999px; border:1px solid rgba(255,255,255,.10); background:rgba(255,255,255,.05); color:rgba(255,255,255,.78);">
              Live Ops
            </span>

            <div class="seg" id="scopeSeg">
              <button type="button" class="seg-btn is-on" data-scope="today">Today</button>
              <button type="button" class="seg-btn" data-scope="all">All</button>
            </div>
          </div>

          <div class="tb-sub">
            <span>Admin: <strong>{{ $admin->username }}</strong></span>
            <span style="opacity:.5;">•</span>
            <span>Role: <strong>{{ $admin->role }}</strong></span>
            <span style="opacity:.5;">•</span>
            <span>Status: <strong>{{ $admin->is_active ? 'Active' : 'Inactive' }}</strong></span>
          </div>
        </div>

        <div class="tb-right">
          <a class="pill" href="{{ route('admin.audit.index') }}">🧾 Audit</a>
          <a class="pill" href="{{ route('admin.wallettx.index') }}">💳 Wallet</a>
          <a class="btn btn-primary" href="{{ route('admin.users.index') }}">👥 Users</a>
        </div>
      </div>

      {{-- TOP STATS (boxes) --}}
      <div class="grid kpis">
        <a class="card kpi glow-blue col-3 reveal" data-tilt="1" href="{{ route('admin.users.index') }}">
          <div class="row">
            <div>
              <div class="label">Total Users</div>
              <div class="value">{{ $nf0($stats['users_total'] ?? 0) }}</div>
              <div class="hint">👥 Open users</div>
            </div>
            <div class="icon">👥</div>
          </div>
        </a>

        <a class="card kpi glow-violet col-3 reveal" data-tilt="1"
           href="{{ route('admin.users.index') }}?from={{ $today }}&to={{ $today }}">
          <div class="row">
            <div>
              <div class="label">New Users Today</div>
              <div class="value">{{ $nf0($stats['users_today'] ?? 0) }}</div>
              <div class="hint">✨ Today signup</div>
            </div>
            <div class="icon">🆕</div>
          </div>
        </a>

        {{-- SWITCH: Bets --}}
        <a class="card kpi glow-green col-3 reveal" data-tilt="1"
           data-href-today="{{ $hrefBetsToday }}"
           data-href-all="{{ $hrefBetsAll }}"
           href="{{ $hrefBetsToday }}"
           data-switch-card="1">
          <div class="row">
            <div>
              <div class="label">Bets</div>
              <div class="value"><span data-kpi="bets" data-decimals="0"></span></div>
              <div class="hint">🎲 Open bet records</div>
            </div>
            <div class="icon">🎲</div>
          </div>
        </a>

        {{-- SWITCH: Stake --}}
        <a class="card kpi glow-warn col-3 reveal" data-tilt="1"
           data-href-today="{{ $hrefBetsToday }}"
           data-href-all="{{ $hrefBetsAll }}"
           href="{{ $hrefBetsToday }}"
           data-switch-card="1">
          <div class="row">
            <div>
              <div class="label">Stake</div>
              <div class="value"><span data-kpi="stake" data-decimals="2"></span></div>
              <div class="hint">💰 Stake sum</div>
            </div>
            <div class="icon">💰</div>
          </div>
        </a>

        {{-- SWITCH: Profit --}}
        <a class="card kpi glow-blue col-3 reveal" data-tilt="1"
           data-href-today="{{ $hrefProfitToday }}"
           data-href-all="{{ $hrefProfitAll }}"
           href="{{ $hrefProfitToday }}"
           data-switch-card="1">
          <div class="row">
            <div>
              <div class="label">Profit</div>
              <div class="value"><span data-kpi="profit" data-decimals="2"></span></div>
              <div class="hint">📈 Filter profit</div>
            </div>
            <div class="icon">📈</div>
          </div>
        </a>

        {{-- SWITCH: Top Game --}}
        <a class="card kpi glow-violet col-3 reveal" data-tilt="1"
           data-href-today="{{ $hrefTopGameToday }}"
           data-href-all="{{ $hrefTopGameAll }}"
           href="{{ $hrefTopGameToday }}"
           data-switch-card="1">
          <div class="row">
            <div>
              <div class="label">Top Game</div>
              <div class="value" style="font-size:16px; line-height:1.1; margin-top:10px;">
                <span data-kpi="top_game_label" data-decimals="-1"></span>
              </div>
              <div class="hint">🔥 Plays: <span data-kpi="top_game_count" data-decimals="0"></span></div>
            </div>
            <div class="icon">🔥</div>
          </div>
        </a>

        <a class="card kpi glow-warn col-3 reveal" data-tilt="1" href="{{ route('admin.kyc.index') }}">
          <div class="row">
            <div>
              <div class="label">KYC Pending</div>
              <div class="value">{{ $nf0($stats['kyc_pending'] ?? 0) }}</div>
              <div class="hint">✅ Approve queue</div>
            </div>
            <div class="icon">✅</div>
          </div>
        </a>

        {{-- SWITCH: Wallet Net --}}
        <a class="card kpi glow-green col-3 reveal" data-tilt="1"
           data-href-today="{{ $hrefWalletToday }}"
           data-href-all="{{ $hrefWalletAll }}"
           href="{{ $hrefWalletToday }}"
           data-switch-card="1">
          <div class="row">
            <div>
              <div class="label">Wallet Net</div>
              <div class="value"><span data-kpi="wallet_net" data-decimals="2"></span></div>
              <div class="hint">💳 Tx: <span data-kpi="wallet_tx" data-decimals="0"></span></div>
            </div>
            <div class="icon">💳</div>
          </div>
        </a>
      </div>

      <script>
        window.__DASH_SCOPE = @json([
          'today' => $statsToday ?? [],
          'all'   => $statsAll ?? [],
        ]);
      </script>

      <div style="height:14px;"></div>

      <div class="grid" style="grid-template-columns: repeat(12, 1fr); gap:14px;">
        {{-- Latest registrations --}}
        <div class="card col-6 reveal">
          <div class="section-title">
            <h3>Latest users</h3>
            <a class="pill" href="{{ route('admin.users.index') }}">View all</a>
          </div>

          <div class="list">
            @forelse($latestUsers as $u)
              <a class="item" href="{{ route('admin.users.edit', $u->id) }}">
                <span class="dot green"></span>
                <div class="i-main">
                  <p class="i-title">{{ $u->username ?? ('User#'.$u->id) }}</p>
                  <div class="i-sub">
                    {{ $u->email ?? '-' }}
                    @if(!empty($u->country) || !empty($u->currency))
                      • {{ $u->country ?? '-' }} {{ $u->currency ?? '' }}
                    @endif
                  </div>
                </div>
                <div class="i-time">{{ \Illuminate\Support\Carbon::parse($u->created_at)->format('m-d H:i') }}</div>
              </a>
            @empty
              <div class="item" style="cursor:default;">
                <span class="dot"></span>
                <div class="i-main">
                  <p class="i-title">No users</p>
                  <div class="i-sub">—</div>
                </div>
              </div>
            @endforelse
          </div>
        </div>

        {{-- Top games today --}}
        <div class="card col-6 reveal">
          <div class="section-title">
            <h3>Top games today</h3>
            <a class="pill" href="{{ route('admin.betrecords.index') }}?from={{ $today }}&to={{ $today }}">Open bets</a>
          </div>

          <div class="list">
            @forelse($topGamesToday as $g)
              @php
                $label = trim(($g->game_name ?: $g->game_code) . ' • ' . ($g->provider_name ?: $g->provider));
              @endphp
              <a class="item"
                 href="{{ route('admin.betrecords.index') }}?game={{ urlencode($g->game_code) }}&provider={{ urlencode($g->provider) }}&from={{ $today }}&to={{ $today }}">
                <span class="dot"></span>
                <div class="i-main">
                  <p class="i-title">{{ $label }}</p>
                  <div class="i-sub">Plays: {{ $nf0($g->cnt) }} • Stake: {{ $nf2($g->stake_sum) }}</div>
                </div>
                <div class="i-time">today</div>
              </a>
            @empty
              <div class="item" style="cursor:default;">
                <span class="dot"></span>
                <div class="i-main">
                  <p class="i-title">No plays today</p>
                  <div class="i-sub">—</div>
                </div>
              </div>
            @endforelse
          </div>

          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn" href="{{ route('admin.dbox.providers.index') }}">🧩 Providers</a>
            <a class="btn" href="{{ route('admin.dbox.games.index') }}">🕹️ Games</a>
            <a class="btn" href="{{ route('admin.dbox.games.sort') }}">↕️ Sorting</a>
            <a class="btn" href="{{ route('admin.dbox.images.upload.form') }}">🖼️ Upload</a>
          </div>
        </div>

        {{-- Recent bets --}}
        <div class="card col-12 reveal">
          <div class="section-title">
            <h3>Recent bets</h3>
            <a class="pill" href="{{ route('admin.betrecords.index') }}">View all</a>
          </div>

          <div class="grid" style="grid-template-columns: repeat(12, 1fr); gap:12px;">
            @forelse($recentBets as $b)
              @php
                $title = trim(($b->username ?: ('User#'.$b->user_id)) . ' • ' . ($b->game_name ?: $b->game_code) . ' • ' . ($b->provider_name ?: $b->provider));
                $q = $b->bet_id ?? $b->round_ref ?? $b->bet_reference ?? $b->id;
              @endphp
              <a class="card col-3 kpi" style="min-height:120px;" href="{{ route('admin.betrecords.index') }}?q={{ urlencode((string)$q) }}">
                <div class="row">
                  <div style="min-width:0;">
                    <div class="label">Bet #{{ $b->id }}</div>
                    <div style="font-weight:950; margin-top:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                      {{ $title }}
                    </div>
                    <div class="hint" style="margin-top:8px;">
                      Stake {{ $nf2($b->stake2) }} • Profit {{ $nf2($b->profit2) }}
                    </div>
                    <div class="hint" style="opacity:.7;">
                      {{ \Illuminate\Support\Carbon::parse($b->bet_at)->format('m-d H:i') }} • {{ $b->status }}
                    </div>
                  </div>
                  <div class="icon">🎯</div>
                </div>
              </a>
            @empty
              <div class="card col-12">
                No recent bets.
              </div>
            @endforelse
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    (function(){
      const data = window.__DASH_SCOPE || {};
      let scope = 'today';

      const fmt = (v, d) => {
        if (d === -1) return (v ?? '—') + '';
        const n = (v === null || v === undefined || v === '') ? 0 : Number(v);
        if (!isFinite(n)) return '0';
        return new Intl.NumberFormat('en-US', {
          minimumFractionDigits: d,
          maximumFractionDigits: d
        }).format(n);
      };

      const apply = () => {
        const s = data[scope] || {};

        document.querySelectorAll('[data-kpi]').forEach(el => {
          const key = el.getAttribute('data-kpi');
          const d = Number(el.getAttribute('data-decimals') || '0');
          el.textContent = fmt(s[key], d);
        });

        document.querySelectorAll('[data-switch-card="1"]').forEach(a => {
          const href = a.getAttribute(scope === 'today' ? 'data-href-today' : 'data-href-all');
          if (href) a.setAttribute('href', href);
        });
      };

      const seg = document.getElementById('scopeSeg');
      if (seg) {
        seg.addEventListener('click', (e) => {
          const btn = e.target.closest('[data-scope]');
          if (!btn) return;
          scope = btn.getAttribute('data-scope') || 'today';
          seg.querySelectorAll('.seg-btn').forEach(b => b.classList.toggle('is-on', b === btn));
          apply();
        });
      }

      apply();
    })();
  </script>
@endsection
