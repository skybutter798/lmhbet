{{-- /home/lmh/app/resources/views/admins/layout-dashboard.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin')</title>

  <style>
    :root{
      --bg0:#070b14;
      --bg1:#0b1220;
      --bg2:#0a0f1a;

      --card: rgba(255,255,255,.06);
      --card2: rgba(255,255,255,.08);
      --border: rgba(255,255,255,.10);

      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.62);

      --a1:#4f8cff;
      --a2:#7c5cff;
      --a3:#27e0a3;
      --danger:#ff4d6d;
      --warn:#ffcc66;

      --r12: 12px;
      --r16: 16px;
      --r20: 20px;

      --t: 180ms cubic-bezier(.2,.9,.2,1);
      --shadow: 0 16px 50px rgba(0,0,0,.45);
      --shadow2: 0 10px 25px rgba(0,0,0,.35);
    }

    *{ box-sizing:border-box; }
    html, body{ height:100%; }
    body{
      margin:0;
      color:var(--text);
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
      background:
        radial-gradient(1200px 600px at 10% -10%, rgba(79,140,255,.20), transparent 60%),
        radial-gradient(900px 500px at 100% 0%, rgba(124,92,255,.18), transparent 55%),
        radial-gradient(800px 450px at 60% 120%, rgba(39,224,163,.10), transparent 60%),
        linear-gradient(180deg, var(--bg0), var(--bg1) 40%, var(--bg2));
      overflow-x:hidden;
    }

    a{ color:inherit; text-decoration:none; }

    .content{
      padding: 22px 22px 40px;
      position: relative;
    }

    .topbar{
      position: sticky;
      top: 0;
      z-index: 50;
      padding: 14px 14px;
      margin: -8px -8px 16px;
      border-radius: var(--r20);
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.06);
      backdrop-filter: blur(10px);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }

    .topbar .tb-left{
      display:flex;
      flex-direction:column;
      gap: 3px;
      min-width: 0;
    }
    .topbar .tb-title{
      font-size: 22px;
      font-weight: 950;
      letter-spacing: .2px;
      display:flex;
      align-items:center;
      gap:10px;
      min-width: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .topbar .tb-sub{
      font-size: 12px;
      color: var(--muted);
      display:flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .tb-right{
      display:flex;
      gap:10px;
      align-items:center;
      flex: 0 0 auto;
    }

    .pill{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding: 9px 12px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.05);
      color: rgba(255,255,255,.88);
      font-weight: 800;
      font-size: 12px;
      transition: transform var(--t), background var(--t), border-color var(--t);
      user-select:none;
      cursor:pointer;
    }
    .pill:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.07);
      border-color: rgba(255,255,255,.16);
    }
    .pill:active{ transform: translateY(0) scale(.98); }

    .btn{
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.06);
      color: rgba(255,255,255,.92);
      padding: 10px 14px;
      border-radius: 14px;
      cursor:pointer;
      font-weight: 900;
      transition: transform var(--t), background var(--t), border-color var(--t), box-shadow var(--t);
      box-shadow: 0 12px 25px rgba(0,0,0,.22);
    }
    .btn:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.08);
      border-color: rgba(255,255,255,.18);
    }
    .btn:active{ transform: translateY(0) scale(.98); }

    .btn-primary{
      background: linear-gradient(135deg, rgba(79,140,255,.90), rgba(124,92,255,.85));
      border-color: rgba(255,255,255,.12);
      box-shadow: 0 18px 40px rgba(79,140,255,.20);
    }
    .btn-primary:hover{
      box-shadow: 0 22px 55px rgba(79,140,255,.26);
    }

    .card{
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.04));
      border: 1px solid rgba(255,255,255,.10);
      border-radius: var(--r20);
      padding: 16px;
      box-shadow: var(--shadow2);
      position: relative;
      overflow: hidden;
    }
    .card::before{
      content:"";
      position:absolute;
      inset:-2px;
      background:
        radial-gradient(500px 160px at 20% 0%, rgba(79,140,255,.18), transparent 55%),
        radial-gradient(450px 160px at 100% 20%, rgba(124,92,255,.14), transparent 55%),
        radial-gradient(420px 180px at 40% 120%, rgba(39,224,163,.10), transparent 55%);
      opacity:.9;
      pointer-events:none;
    }
    .card > *{ position: relative; z-index: 1; }

    .grid{
      display:grid;
      gap: 14px;
    }
    .grid.kpis{
      grid-template-columns: repeat(12, 1fr);
    }
    .col-12{ grid-column: span 12; }
    .col-8{ grid-column: span 8; }
    .col-6{ grid-column: span 6; }
    .col-4{ grid-column: span 4; }
    .col-3{ grid-column: span 3; }

    @media (max-width: 1100px){
      .col-3{ grid-column: span 6; }
      .col-4{ grid-column: span 6; }
      .col-8{ grid-column: span 12; }
    }
    @media (max-width: 680px){
      .content{ padding: 16px 14px 32px; }
      .topbar{ margin: -6px -6px 14px; }
      .col-3,.col-4,.col-6{ grid-column: span 12; }
    }

    .kpi{
      cursor:pointer;
      transition: transform var(--t), border-color var(--t), background var(--t), box-shadow var(--t);
      will-change: transform;
      min-height: 110px;
    }
    .kpi:hover{
      transform: translateY(-2px);
      border-color: rgba(255,255,255,.18);
      box-shadow: 0 22px 60px rgba(0,0,0,.40);
    }
    .kpi:active{ transform: translateY(0) scale(.99); }

    .kpi .row{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap: 10px;
    }
    .kpi .label{
      font-size: 12px;
      color: var(--muted);
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
    }
    .kpi .value{
      margin-top: 8px;
      font-size: 28px;
      font-weight: 950;
      letter-spacing: .2px;
      line-height: 1.0;
    }
    .kpi .hint{
      margin-top: 8px;
      font-size: 12px;
      color: rgba(255,255,255,.70);
      display:flex;
      gap:8px;
      align-items:center;
    }

    .kpi .icon{
      width: 44px; height: 44px;
      border-radius: 16px;
      display:grid; place-items:center;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.06);
      font-size: 18px;
      flex: 0 0 auto;
    }

    .glow-blue  { box-shadow: 0 18px 50px rgba(79,140,255,.12); }
    .glow-violet{ box-shadow: 0 18px 50px rgba(124,92,255,.12); }
    .glow-green { box-shadow: 0 18px 50px rgba(39,224,163,.12); }
    .glow-warn  { box-shadow: 0 18px 50px rgba(255,204,102,.10); }

    .section-title{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 10px;
      margin-bottom: 10px;
    }
    .section-title h3{
      margin: 0;
      font-size: 14px;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(255,255,255,.72);
      font-weight: 950;
    }
    .section-title .mini{
      font-size: 12px;
      color: var(--muted);
      font-weight: 800;
    }

    .list{
      display:flex;
      flex-direction:column;
      gap: 10px;
    }
    .item{
      padding: 12px 12px;
      border-radius: 14px;
      border: 1px solid rgba(255,255,255,.08);
      background: rgba(255,255,255,.04);
      display:flex;
      gap: 10px;
      align-items:flex-start;
      cursor:pointer;
      transition: transform var(--t), border-color var(--t), background var(--t);
      position: relative;
      overflow:hidden;
    }
    .item:hover{
      transform: translateY(-1px);
      border-color: rgba(255,255,255,.14);
      background: rgba(255,255,255,.06);
    }
    .item:active{ transform: translateY(0) scale(.995); }

    .dot{
      width: 10px; height: 10px;
      border-radius: 99px;
      margin-top: 5px;
      background: rgba(79,140,255,.9);
      box-shadow: 0 0 0 6px rgba(79,140,255,.12);
      flex: 0 0 auto;
    }
    .dot.green{ background: rgba(39,224,163,.95); box-shadow: 0 0 0 6px rgba(39,224,163,.12); }
    .dot.warn { background: rgba(255,204,102,.95); box-shadow: 0 0 0 6px rgba(255,204,102,.12); }
    .dot.red  { background: rgba(255,77,109,.95); box-shadow: 0 0 0 6px rgba(255,77,109,.12); }

    .item .i-main{ min-width:0; }
    .item .i-title{
      margin:0;
      font-size: 13px;
      font-weight: 900;
      line-height: 1.2;
      white-space: nowrap;
      overflow:hidden;
      text-overflow: ellipsis;
    }
    .item .i-sub{
      margin-top: 4px;
      font-size: 12px;
      color: var(--muted);
      white-space: nowrap;
      overflow:hidden;
      text-overflow: ellipsis;
    }
    .item .i-time{
      margin-left:auto;
      color: rgba(255,255,255,.62);
      font-weight: 800;
      font-size: 12px;
      flex: 0 0 auto;
    }

    .reveal{
      opacity:0;
      transform: translateY(10px);
      transition: opacity 520ms ease, transform 520ms ease;
    }
    .reveal.is-in{
      opacity:1;
      transform: translateY(0);
    }

    .orb{
      position: absolute;
      width: 420px;
      height: 420px;
      border-radius: 999px;
      filter: blur(60px);
      opacity: .35;
      pointer-events:none;
      mix-blend-mode: screen;
      animation: floaty 8s ease-in-out infinite;
    }
    .orb.o1{ left:-140px; top: 40px; background: rgba(79,140,255,.65); }
    .orb.o2{ right:-160px; top: 160px; background: rgba(124,92,255,.55); animation-duration: 10s; }
    .orb.o3{ right: 40px; bottom: -220px; background: rgba(39,224,163,.45); animation-duration: 12s; }

    @keyframes floaty{
      0%,100%{ transform: translate(0,0) scale(1); }
      50%{ transform: translate(10px,-14px) scale(1.03); }
    }

    @media (prefers-reduced-motion: reduce){
      .orb{ animation: none; }
      .reveal{ transition:none; }
      .kpi,.item,.btn,.pill{ transition:none; }
    }

    /* mini segmented switch */
    .seg{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding: 4px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.04);
      margin-left: 10px;
    }
    .seg-btn{
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.04);
      color: rgba(255,255,255,.80);
      font-weight: 900;
      font-size: 12px;
      padding: 6px 10px;
      border-radius: 999px;
      cursor: pointer;
      transition: transform var(--t), background var(--t), border-color var(--t);
    }
    .seg-btn:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.06);
      border-color: rgba(255,255,255,.16);
    }
    .seg-btn.is-on{
      background: linear-gradient(135deg, rgba(79,140,255,.90), rgba(124,92,255,.85));
      color: rgba(255,255,255,.95);
      border-color: rgba(255,255,255,.14);
    }
  </style>
</head>
<body>
  @yield('body')

  <script>
    (function(){
      const els = document.querySelectorAll('.reveal');
      requestAnimationFrame(() => {
        els.forEach((el, i) => setTimeout(() => el.classList.add('is-in'), 60 + i*60));
      });
    })();

    (function(){
      const cards = document.querySelectorAll('[data-tilt="1"]');
      const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

      cards.forEach(card => {
        let rAF = null;

        function onMove(e){
          const rect = card.getBoundingClientRect();
          const x = (e.clientX - rect.left) / rect.width;
          const y = (e.clientY - rect.top) / rect.height;
          const rx = clamp((0.5 - y) * 6, -6, 6);
          const ry = clamp((x - 0.5) * 8, -8, 8);

          if (rAF) cancelAnimationFrame(rAF);
          rAF = requestAnimationFrame(() => {
            card.style.transform = `translateY(-2px) rotateX(${rx}deg) rotateY(${ry}deg)`;
          });
        }

        function onLeave(){
          if (rAF) cancelAnimationFrame(rAF);
          card.style.transform = '';
        }

        card.addEventListener('mousemove', onMove);
        card.addEventListener('mouseleave', onLeave);
      });
    })();
  </script>
</body>
</html>
