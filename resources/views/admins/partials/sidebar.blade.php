{{-- /home/lmh/app/resources/views/admins/partials/sidebar.blade.php --}}

@php
  $admin = Auth::guard('admin')->user();
  $username = optional($admin)->username ?? 'Admin';
  $initial = strtoupper(mb_substr($username, 0, 1));
@endphp

<style>
  /* =========================================================
     LMH Admin Sidebar (HARDENED)
     - namespaced selectors to avoid collisions
     - forces fixed width + prevents table/content from shrinking sidebar
     - optional collapse toggle (saved in localStorage)
     ========================================================= */

  :root{
    --l-sb-w: 280px;
    --l-sb-w-collapsed: 88px;

    --l-sb-bg: #0b1220;
    --l-sb-bg-2: #0a0f1a;
    --l-sb-border: rgba(255,255,255,.08);

    --l-sb-text: rgba(255,255,255,.88);
    --l-sb-muted: rgba(255,255,255,.62);

    --l-sb-accent: #4f8cff;
    --l-sb-accent-2: #7c5cff;
    --l-sb-danger: #ff4d6d;

    --l-sb-radius: 16px;
    --l-sb-item-radius: 12px;
    --l-sb-trans: 180ms cubic-bezier(.2,.9,.2,1);
    --l-sb-shadow: 0 12px 30px rgba(0,0,0,.35);

    --l-font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
  }

  /* Layout hardening: keep sidebar width stable across pages */
  .app{
    display:flex !important;
    min-height:100vh;
    width:100%;
  }
  .app > .content{
    flex: 1 1 auto !important;
    min-width: 0 !important;   /* KEY: allows flex child to shrink so sidebar won't get squeezed */
    width: auto !important;
    overflow: hidden;          /* prevents wide tables from forcing page-level horizontal scroll */
  }

  /* Sidebar container */
  #l-adminSidebar.l-adminSidebar{
    --_w: var(--l-sb-w);

    width: var(--_w) !important;
    min-width: var(--_w) !important;
    max-width: var(--_w) !important;
    flex: 0 0 var(--_w) !important;

    min-height: 100vh;
    position: sticky;
    top: 0;

    background:
      radial-gradient(1200px 600px at 30% -10%, rgba(79,140,255,.18), transparent 55%),
      radial-gradient(900px 500px at 100% 10%, rgba(124,92,255,.12), transparent 45%),
      linear-gradient(180deg, var(--l-sb-bg), var(--l-sb-bg-2));

    border-right: 1px solid var(--l-sb-border);
    box-shadow: var(--l-sb-shadow);

    font-family: var(--l-font);
    color: var(--l-sb-text);

    display:flex;
    flex-direction:column;
    padding: 18px 14px;

    z-index: 100; /* stay above content */
  }

  #l-adminSidebar.l-adminSidebar,
  #l-adminSidebar.l-adminSidebar *{
    box-sizing:border-box;
  }

  /* Collapsed state */
  #l-adminSidebar.l-adminSidebar.is-collapsed{
    --_w: var(--l-sb-w-collapsed);
  }

  /* Brand */
  #l-adminSidebar .l-sb-brand{
    display:flex;
    align-items:center;
    gap: 10px;
    padding: 10px 10px 14px;
    margin-bottom: 6px;
  }
  #l-adminSidebar .l-sb-logo{
    width: 40px; height: 40px;
    border-radius: 14px;
    display:grid; place-items:center;
    font-weight: 900;
    letter-spacing: .5px;
    color: #fff;
    background: linear-gradient(135deg, var(--l-sb-accent), var(--l-sb-accent-2));
    box-shadow: 0 10px 20px rgba(79,140,255,.18);
    flex: 0 0 auto;
  }
  #l-adminSidebar .l-sb-titlewrap{
    display:flex;
    flex-direction:column;
    line-height: 1.1;
    min-width: 0;
  }
  #l-adminSidebar .l-sb-title{
    font-size: 14px;
    font-weight: 900;
    letter-spacing: .2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  #l-adminSidebar .l-sb-subtitle{
    font-size: 12px;
    color: var(--l-sb-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Collapse button */
  #l-adminSidebar .l-sb-toggle{
    margin-left:auto;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.9);
    cursor: pointer;
    transition: transform var(--l-sb-trans), background var(--l-sb-trans), border-color var(--l-sb-trans);
    display:grid;
    place-items:center;
    flex: 0 0 auto;
  }
  #l-adminSidebar .l-sb-toggle:hover{
    transform: translateY(-1px);
    background: rgba(255,255,255,.08);
    border-color: rgba(255,255,255,.14);
  }
  #l-adminSidebar .l-sb-toggle:active{
    transform: translateY(0px) scale(.98);
  }

  /* User card */
  #l-adminSidebar .l-sb-user{
    margin: 10px 10px 14px;
    padding: 12px 12px;
    border-radius: var(--l-sb-radius);
    border: 1px solid var(--l-sb-border);
    background: rgba(255,255,255,.03);
    display:flex;
    gap: 10px;
    align-items:center;
  }
  #l-adminSidebar .l-sb-avatar{
    width: 36px; height: 36px;
    border-radius: 12px;
    background: rgba(255,255,255,.08);
    display:grid; place-items:center;
    color: rgba(255,255,255,.85);
    font-weight: 900;
    flex: 0 0 auto;
  }
  #l-adminSidebar .l-sb-user-meta{
    min-width: 0;
    display:flex;
    flex-direction:column;
    gap: 2px;
  }
  #l-adminSidebar .l-sb-user-label{
    font-size: 11px;
    color: var(--l-sb-muted);
  }
  #l-adminSidebar .l-sb-user-name{
    font-size: 13px;
    font-weight: 900;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Sections */
  #l-adminSidebar .l-sb-section{
    margin: 12px 6px 0;
  }
  #l-adminSidebar .l-sb-section-title{
    font-size: 11px;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: rgba(255,255,255,.55);
    padding: 8px 10px;
  }

  /* Nav */
  #l-adminSidebar .l-sb-nav{
    display:flex;
    flex-direction:column;
    gap: 6px;
    padding: 0 6px;
  }
  #l-adminSidebar .l-sb-link{
    display:flex;
    align-items:center;
    gap: 10px;

    padding: 10px 10px;
    border-radius: var(--l-sb-item-radius);
    text-decoration:none;
    color: var(--l-sb-text);

    border: 1px solid transparent;
    background: transparent;

    transition: transform var(--l-sb-trans),
                background var(--l-sb-trans),
                border-color var(--l-sb-trans),
                box-shadow var(--l-sb-trans);

    position: relative;
    overflow: hidden;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
  }
  #l-adminSidebar .l-sb-link::before{
    content:"";
    position:absolute;
    inset:0;
    background: linear-gradient(90deg, rgba(79,140,255,.16), rgba(124,92,255,.10), transparent 70%);
    opacity: 0;
    transition: opacity var(--l-sb-trans);
  }
  #l-adminSidebar .l-sb-link:hover{
    background: rgba(255,255,255,.04);
    border-color: rgba(255,255,255,.08);
    transform: translateY(-1px);
  }
  #l-adminSidebar .l-sb-link:hover::before{ opacity: 1; }

  #l-adminSidebar .l-sb-link.is-active{
    background: rgba(79,140,255,.12);
    border-color: rgba(79,140,255,.25);
    box-shadow: 0 8px 18px rgba(79,140,255,.12);
  }
  #l-adminSidebar .l-sb-link.is-active::before{ opacity: 1; }

  #l-adminSidebar .l-sb-icon{
    width: 34px; height: 34px;
    border-radius: 12px;
    display:grid; place-items:center;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.08);
    color: rgba(255,255,255,.9);
    flex: 0 0 auto;
  }
  #l-adminSidebar .l-sb-text{
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    position: relative;
    z-index: 1;
  }

  /* Footer */
  #l-adminSidebar .l-sb-footer{
    margin-top: auto;
    padding: 14px 10px 8px;
  }
  #l-adminSidebar .l-sb-logout{
    width: 100%;
    border: 1px solid rgba(255,77,109,.28);
    background: rgba(255,77,109,.10);
    color: rgba(255,255,255,.92);
    border-radius: 14px;
    padding: 10px 12px;
    font-weight: 900;
    cursor: pointer;
    transition: transform var(--l-sb-trans), background var(--l-sb-trans), border-color var(--l-sb-trans);
  }
  #l-adminSidebar .l-sb-logout:hover{
    background: rgba(255,77,109,.16);
    border-color: rgba(255,77,109,.40);
    transform: translateY(-1px);
  }

  /* Collapsed visibility rules */
  #l-adminSidebar.is-collapsed .l-sb-titlewrap{ display:none; }
  #l-adminSidebar.is-collapsed .l-sb-user-meta{ display:none; }
  #l-adminSidebar.is-collapsed .l-sb-section-title{ display:none; }
  #l-adminSidebar.is-collapsed .l-sb-text{ display:none; }

  /* Make collapsed sidebar still feel aligned */
  #l-adminSidebar.is-collapsed .l-sb-brand{
    justify-content: space-between;
  }
  #l-adminSidebar.is-collapsed .l-sb-user{
    justify-content: center;
    padding: 12px 8px;
  }
  #l-adminSidebar.is-collapsed .l-sb-nav{
    padding: 0 8px;
  }
  #l-adminSidebar.is-collapsed .l-sb-link{
    justify-content: center;
  }

  @media (prefers-reduced-motion: reduce){
    #l-adminSidebar *{ transition: none !important; }
  }
</style>

<style>
  /* add below your sidebar styles */
  #l-adminSidebar .l-sb-mini{
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.92);
    padding: 8px 10px;
    border-radius: 12px;
    cursor:pointer;
    font-weight: 900;
    font-size: 12px;
    transition: transform var(--l-sb-trans), background var(--l-sb-trans), border-color var(--l-sb-trans);
  }
  #l-adminSidebar .l-sb-mini:hover{
    transform: translateY(-1px);
    background: rgba(255,255,255,.08);
    border-color: rgba(255,255,255,.16);
  }
  #l-adminSidebar.is-collapsed .l-sb-mini{ display:none; }
</style>

<aside id="l-adminSidebar" class="l-adminSidebar" aria-label="Admin Sidebar">
  {{-- Brand --}}
  <div class="l-sb-brand">
    <div class="l-sb-logo">LMH</div>

    <div class="l-sb-titlewrap">
      <div class="l-sb-title">LMH Admin Panel</div>
      <div class="l-sb-subtitle">Management Console</div>
    </div>

    <button type="button" class="l-sb-toggle" id="lSbToggle" title="Toggle sidebar">
      ☰
    </button>
  </div>

  {{-- User --}}
  <div class="l-sb-user" title="{{ $username }}">
    <div class="l-sb-avatar">{{ $initial }}</div>
    <div class="l-sb-user-meta">
      <div class="l-sb-user-label">Signed in as</div>
      <div class="l-sb-user-name">{{ $username }}</div>
      <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
        <button type="button" class="l-sb-mini" id="lOpenProfile">⚙️ Profile</button>
      </div>
    </div>
  </div>

  {{-- Core --}}
  <div class="l-sb-section">
    <div class="l-sb-section-title">Core</div>
    <nav class="l-sb-nav">
      <a class="l-sb-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
         href="{{ route('admin.dashboard') }}">
        <span class="l-sb-icon">🏠</span>
        <span class="l-sb-text">Dashboard</span>
      </a>

      <a class="l-sb-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
         href="{{ route('admin.users.index') }}">
        <span class="l-sb-icon">👥</span>
        <span class="l-sb-text">User Management</span>
      </a>

      <a class="l-sb-link {{ request()->routeIs('admin.kyc.*') ? 'is-active' : '' }}"
         href="{{ route('admin.kyc.index') }}">
        <span class="l-sb-icon">✅</span>
        <span class="l-sb-text">KYC Approvals</span>
      </a>
    </nav>
  </div>

  {{-- Monitoring --}}
  <div class="l-sb-section">
    <div class="l-sb-section-title">Monitoring</div>
    <nav class="l-sb-nav">
      <a class="l-sb-link {{ request()->routeIs('admin.audit.*') ? 'is-active' : '' }}"
         href="{{ route('admin.audit.index') }}">
        <span class="l-sb-icon">🧾</span>
        <span class="l-sb-text">Audit Logs</span>
      </a>

      <a class="l-sb-link {{ request()->routeIs('admin.betrecords.*') ? 'is-active' : '' }}"
         href="{{ route('admin.betrecords.index') }}">
        <span class="l-sb-icon">🎲</span>
        <span class="l-sb-text">Bet Records</span>
      </a>

      <a class="l-sb-link {{ request()->routeIs('admin.wallettx.*') ? 'is-active' : '' }}"
         href="{{ route('admin.wallettx.index') }}">
        <span class="l-sb-icon">💳</span>
        <span class="l-sb-text">Wallet Transactions</span>
      </a>
      
      <a class="l-sb-link {{ request()->routeIs('admin.support.*') ? 'is-active' : '' }}"
         href="{{ route('admin.support.index') }}">
        <span class="l-sb-icon">🎧</span>
        <span class="l-sb-text">Support Ticket</span>
      </a>
    </nav>
  </div>

  {{-- DBOX --}}
  <div class="l-sb-section">
    <div class="l-sb-section-title">DBOX</div>
    <nav class="l-sb-nav">
      <a class="l-sb-link {{ request()->routeIs('admin.dbox.providers.*') ? 'is-active' : '' }}"
         href="{{ route('admin.dbox.providers.index') }}">
        <span class="l-sb-icon">🧩</span>
        <span class="l-sb-text">Providers</span>
      </a>

      <a class="l-sb-link {{ request()->routeIs('admin.dbox.games.*') && !request()->routeIs('admin.dbox.games.sort') ? 'is-active' : '' }}"
         href="{{ route('admin.dbox.games.index') }}">
        <span class="l-sb-icon">🕹️</span>
        <span class="l-sb-text">Games</span>
      </a>

      <a class="l-sb-link {{ request()->routeIs('admin.dbox.games.sort') ? 'is-active' : '' }}"
         href="{{ route('admin.dbox.games.sort') }}">
        <span class="l-sb-icon">↕️</span>
        <span class="l-sb-text">Game Sorting</span>
      </a>

      <a class="l-sb-link {{ request()->routeIs('admin.dbox.images.upload.*') ? 'is-active' : '' }}"
         href="{{ route('admin.dbox.images.upload.form') }}">
        <span class="l-sb-icon">🖼️</span>
        <span class="l-sb-text">Image Upload</span>
      </a>
    </nav>
  </div>

  {{-- Footer --}}
  <div class="l-sb-footer">
    <form method="POST" action="{{ route('admin.logout') }}">
      @csrf
      <button class="l-sb-logout" type="submit">Logout</button>
    </form>
  </div>
</aside>

{{-- Profile Modal Styles (GLOBAL, so modal shows as real overlay) --}}
<style>
  /* Scoped modal styling (no global vars) */
  #lpfWrap.lpf-wrap{
    --lpf-bg: rgba(10,16,28,.74);
    --lpf-border: rgba(255,255,255,.10);
    --lpf-text: rgba(255,255,255,.92);
    --lpf-muted: rgba(255,255,255,.62);
    --lpf-a1:#4f8cff;
    --lpf-a2:#7c5cff;
    --lpf-danger:#ff4d6d;
    --lpf-ok:#27e0a3;

    --lpf-r14: 14px;
    --lpf-r18: 18px;
    --lpf-r22: 22px;

    --lpf-t: 180ms cubic-bezier(.2,.9,.2,1);
    --lpf-shadow: 0 20px 80px rgba(0,0,0,.60);

    --lpf-font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;

    position:fixed;
    inset:0;
    z-index: 99999;
    font-family: var(--lpf-font);
    color: var(--lpf-text);
  }

  #lpfWrap .lpf-backdrop{
    position:absolute; inset:0;
    background: var(--lpf-bg);
    backdrop-filter: blur(10px);
  }

  #lpfWrap .lpf-modal{
    position:absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%,-50%);
    width: min(760px, calc(100vw - 28px));
    border-radius: var(--lpf-r22);
    border: 1px solid var(--lpf-border);
    background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.05));
    box-shadow: var(--lpf-shadow);
    overflow:hidden;
  }

  /* header */
  #lpfWrap .lpf-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding: 16px 16px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.03);
  }
  #lpfWrap .lpf-title{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:0;
  }
  #lpfWrap .lpf-badge{
    width: 42px; height: 42px;
    border-radius: 16px;
    display:grid; place-items:center;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.10);
    flex: 0 0 auto;
  }
  #lpfWrap .lpf-titletext{ min-width:0; }
  #lpfWrap .lpf-h1{
    font-weight: 950;
    letter-spacing: .2px;
    line-height: 1.1;
  }
  #lpfWrap .lpf-sub{
    margin-top: 3px;
    font-size: 12px;
    color: var(--lpf-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 420px;
  }

  #lpfWrap .lpf-headright{
    display:flex;
    align-items:center;
    gap: 10px;
    flex: 0 0 auto;
  }

  #lpfWrap .lpf-status{
    display:flex;
    align-items:center;
    gap: 8px;
    font-size: 12px;
    font-weight: 900;
    color: rgba(255,255,255,.72);
    padding: 8px 10px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.04);
  }
  #lpfWrap .lpf-dot{
    width: 10px; height: 10px;
    border-radius: 99px;
    background: rgba(255,255,255,.30);
    box-shadow: 0 0 0 6px rgba(255,255,255,.06);
  }
  #lpfWrap .lpf-dot.on{
    background: rgba(39,224,163,.95);
    box-shadow: 0 0 0 6px rgba(39,224,163,.12);
  }

  #lpfWrap .lpf-x{
    width: 40px; height: 40px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.05);
    color: rgba(255,255,255,.9);
    cursor: pointer;
    transition: transform var(--lpf-t), background var(--lpf-t), border-color var(--lpf-t);
    display:grid;
    place-items:center;
    font-weight: 950;
  }
  #lpfWrap .lpf-x:hover{ transform: translateY(-1px); background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.16); }
  #lpfWrap .lpf-x:active{ transform: translateY(0) scale(.98); }

  /* tabs */
  #lpfWrap .lpf-tabs{
    display:flex;
    align-items:center;
    gap:8px;
    padding: 10px 12px;
    border-bottom: 1px solid rgba(255,255,255,.08);
  }
  #lpfWrap .lpf-tab{
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.05);
    color: rgba(255,255,255,.78);
    font-weight: 950;
    font-size: 12px;
    padding: 8px 12px;
    border-radius: 999px;
    cursor: pointer;
    transition: transform var(--lpf-t), background var(--lpf-t), border-color var(--lpf-t);
  }
  #lpfWrap .lpf-tab:hover{ transform: translateY(-1px); background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.16); }
  #lpfWrap .lpf-tab.is-on{
    background: linear-gradient(135deg, rgba(79,140,255,.92), rgba(124,92,255,.88));
    color: rgba(255,255,255,.96);
    border-color: rgba(255,255,255,.14);
  }
  #lpfWrap .lpf-spacer{ flex:1; }
  #lpfWrap .lpf-minihelp{
    font-size: 12px;
    color: rgba(255,255,255,.62);
    font-weight: 800;
  }

  /* body */
  #lpfWrap .lpf-body{ padding: 14px 16px 18px; }

  #lpfWrap .lpf-alert{
    border-radius: var(--lpf-r14);
    padding: 10px 12px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.04);
    color: rgba(255,255,255,.92);
    font-weight: 900;
    margin-bottom: 12px;
  }
  #lpfWrap .lpf-alert.ok{ border-color: rgba(39,224,163,.28); background: rgba(39,224,163,.10); }
  #lpfWrap .lpf-alert.err{ border-color: rgba(255,77,109,.28); background: rgba(255,77,109,.10); }

  #lpfWrap .lpf-pane{ display:none; }
  #lpfWrap .lpf-pane.is-on{ display:block; }

  /* inner card */
  #lpfWrap .lpf-card{
    border-radius: var(--lpf-r22);
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.04);
    padding: 18px;
  }
  #lpfWrap .lpf-cardhead{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap: 12px;
    margin-bottom: 10px;
  }
  #lpfWrap .lpf-cardtitle{
    font-weight: 950;
    letter-spacing: .2px;
  }
  #lpfWrap .lpf-cardhint{
    font-size: 12px;
    color: rgba(255,255,255,.60);
    font-weight: 800;
  }

  #lpfWrap .lpf-label{
    display:block;
    margin: 14px 0 6px;
    font-size: 12px;
    font-weight: 950;
    color: rgba(255,255,255,.72);
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  #lpfWrap .lpf-input{
    width: 100%;
    padding: 12px 14px;
    border-radius: var(--lpf-r14);
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(10,18,35,.55);
    color: rgba(255,255,255,.92);
    outline: none;
    font-size: 14px;
    line-height: 1.2;
    box-sizing: border-box;
  }
  #lpfWrap .lpf-input:focus{
    border-color: rgba(79,140,255,.38);
    box-shadow: 0 0 0 4px rgba(79,140,255,.12);
  }

  #lpfWrap .lpf-row2{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-top: 6px;
  }
  @media (max-width: 720px){
    #lpfWrap .lpf-row2{ grid-template-columns: 1fr; }
    #lpfWrap .lpf-sub{ max-width: 260px; }
    #lpfWrap .lpf-modal{ width: calc(100vw - 18px); }
    #lpfWrap .lpf-body{ padding: 12px 12px 16px; }
    #lpfWrap .lpf-card{ padding: 14px; }
  }

  #lpfWrap .lpf-actions{
    display:flex;
    justify-content:flex-end;
    gap: 10px;
    margin-top: 18px;
    flex-wrap: wrap;
  }

  #lpfWrap .lpf-btn{
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.92);
    padding: 10px 14px;
    border-radius: var(--lpf-r14);
    cursor:pointer;
    font-weight: 950;
    transition: transform var(--lpf-t), background var(--lpf-t), border-color var(--lpf-t), opacity var(--lpf-t);
  }
  #lpfWrap .lpf-btn:hover{ transform: translateY(-1px); background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.18); }
  #lpfWrap .lpf-btn:active{ transform: translateY(0) scale(.99); }
  #lpfWrap .lpf-btn:disabled{ opacity: .7; cursor: not-allowed; }

  #lpfWrap .lpf-btn-primary{
    background: linear-gradient(135deg, rgba(79,140,255,.92), rgba(124,92,255,.88));
    border-color: rgba(255,255,255,.12);
  }
  #lpfWrap .lpf-btn-danger{
    background: rgba(255,77,109,.14);
    border-color: rgba(255,77,109,.30);
    color: rgba(255,255,255,.92);
  }

  #lpfWrap .lpf-divider{
    height:1px;
    background: rgba(255,255,255,.08);
    margin: 16px 0;
  }
</style>

<script>
(function () {
  const sidebar = document.getElementById('l-adminSidebar');
  const btn = document.getElementById('lSbToggle');
  if (!sidebar || !btn) return;

  const KEY = 'l-adminSidebar:collapsed';

  function applyFromStorage() {
    const v = localStorage.getItem(KEY);
    if (v === '1') sidebar.classList.add('is-collapsed');
    else sidebar.classList.remove('is-collapsed');
  }

  function toggle() {
    sidebar.classList.toggle('is-collapsed');
    localStorage.setItem(KEY, sidebar.classList.contains('is-collapsed') ? '1' : '0');
  }

  applyFromStorage();
  btn.addEventListener('click', toggle);
})();
</script>

<script>
/**
 * Profile modal initializer
 * - close on X / Cancel / backdrop
 * - tabs (Password / PIN / 2FA)
 * - form submits via fetch JSON
 */
(function(){
  if (window.LPF_initProfileModal) return;

  window.LPF_initProfileModal = function LPF_initProfileModal(wrapEl){
    const wrap = wrapEl || document.getElementById('lpfWrap');
    if (!wrap) return;

    if (wrap.dataset.lpfInit === '1') return;
    wrap.dataset.lpfInit = '1';

    const alertBox  = wrap.querySelector('#lpfAlert');
    const statusDot = wrap.querySelector('#lpfStatusDot');
    const statusText= wrap.querySelector('#lpfStatusText');

    function showMsg(ok, msg){
      if (!alertBox) return;
      alertBox.style.display = '';
      alertBox.classList.remove('ok','err');
      alertBox.classList.add(ok ? 'ok' : 'err');
      alertBox.textContent = msg || (ok ? 'Saved' : 'Error');
    }

    function clearMsg(){
      if (!alertBox) return;
      alertBox.style.display = 'none';
      alertBox.textContent = '';
      alertBox.classList.remove('ok','err');
    }

    function set2faUi(enabled){
      if (!statusDot || !statusText) return;
      statusDot.classList.toggle('on', !!enabled);
      statusText.textContent = enabled ? '2FA enabled' : '2FA disabled';
    }

    function close(){
      try { wrap.remove(); } catch(e){}
      document.removeEventListener('keydown', onKeydown);
    }

    function onKeydown(e){
      if (e.key === 'Escape') close();
    }

    // Close (X + Cancel + backdrop)
    wrap.addEventListener('click', (e) => {
      const closer = e.target && e.target.closest ? e.target.closest('[data-lpf-close="1"]') : null;
      if (closer) close();
    });

    // Esc
    document.addEventListener('keydown', onKeydown);

    // Tabs
    const tabBtns = wrap.querySelectorAll('[data-lpf-tab]');
    const panes   = wrap.querySelectorAll('[data-lpf-pane]');

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const key = btn.getAttribute('data-lpf-tab');

        tabBtns.forEach(b => {
          const on = (b === btn);
          b.classList.toggle('is-on', on);
          b.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        panes.forEach(p => {
          p.classList.toggle('is-on', p.getAttribute('data-lpf-pane') === key);
        });

        clearMsg();
      });
    });

    async function postForm(url, formEl){
      clearMsg();

      const fd = new FormData(formEl);
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: fd
      });

      let data = null;
      try { data = await res.json(); } catch(e){}

      if (!res.ok) {
        if (data && data.errors) {
          const firstKey = Object.keys(data.errors)[0];
          const firstMsg = data.errors[firstKey]?.[0] || 'Validation error';
          showMsg(false, firstMsg);
        } else {
          showMsg(false, (data && data.message) ? data.message : ('Request failed: ' + res.status));
        }
        return;
      }

      showMsg(true, (data && data.message) ? data.message : 'Saved');

      if (formEl && (formEl.id === 'lpfForm2faDisable')) set2faUi(false);
      if (formEl && (formEl.id === 'lpfForm2fa')) {
        const secret = (formEl.querySelector('[name="two_fa_secret"]')?.value || '').trim();
        set2faUi(!!secret);
      }

      formEl.reset();
    }

    const fPw     = wrap.querySelector('#lpfFormPw');
    const fPin    = wrap.querySelector('#lpfFormPin');
    const f2fa    = wrap.querySelector('#lpfForm2fa');
    const f2faDis = wrap.querySelector('#lpfForm2faDisable');

    if (fPw) fPw.addEventListener('submit', (e) => {
      e.preventDefault();
      postForm("{{ route('admin.profile.password.update') }}", fPw);
    });

    if (fPin) fPin.addEventListener('submit', (e) => {
      e.preventDefault();
      postForm("{{ route('admin.profile.pin.update') }}", fPin);
    });

    if (f2fa) f2fa.addEventListener('submit', (e) => {
      e.preventDefault();
      postForm("{{ route('admin.profile.2fa.update') }}", f2fa);
    });

    if (f2faDis) f2faDis.addEventListener('submit', (e) => {
      e.preventDefault();
      postForm("{{ route('admin.profile.2fa.update') }}", f2faDis);
    });
  };
})();
</script>

<script>
(function(){
  const btn = document.getElementById('lOpenProfile');
  if (!btn) return;

  async function openProfileModal(){
    const res = await fetch("{{ route('admin.profile.modal') }}", {
      headers: { 'Accept': 'text/html' }
    });

    const html = await res.text();

    // remove existing modal if present
    const old = document.getElementById('lpfWrap');
    if (old) old.remove();

    // parse HTML and extract modal root
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const wrap = doc.querySelector('#lpfWrap');
    if (!wrap) return;

    document.body.appendChild(wrap);

    // init modal behaviors
    window.LPF_initProfileModal(wrap);
  }

  btn.addEventListener('click', openProfileModal);
})();
</script>
