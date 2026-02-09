{{-- /home/lmh/app/resources/views/admins/layout.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin')</title>

  <style>
    body { margin:0; font-family: Arial, sans-serif; background:#0b1220; color:#e6e6e6; }
    a { color: inherit; text-decoration:none; }
    .app { display:flex; min-height:100vh; }
    .sidebar { width:260px; background:#0f1b33; padding:16px; box-sizing:border-box; }
    .content { flex:1; padding:24px; }
    .card { background:#111f3a; border:1px solid #1f335c; border-radius:10px; padding:16px; }
    .nav-item { display:block; padding:10px 12px; border-radius:8px; margin:6px 0; }
    .nav-item:hover { background:#13244a; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .btn { background:#1f5eff; border:none; color:#fff; padding:10px 14px; border-radius:8px; cursor:pointer; }
    .btn-danger { background:#ff3b3b; }
    .input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #233a6a; background:#0b1731; color:#fff; box-sizing:border-box; }
    .label { display:block; margin:10px 0 6px; color:#b8c3dd; }
    .err { color:#ff8a8a; margin-top:6px; font-size: 13px; }
  </style>
</head>
<body>
  @yield('body')
</body>
</html>
