<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ $title ?? config('app.name') }}</title>

  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('css/account.css') }}">

  @stack('styles')
</head>
<body class="page">
  @yield('body')
  
  @include('partials.mobile-nav')

  {{-- ✅ Make auth modal available on ALL pages --}}
  @include('partials.auth-modal')
  
    @if(request('auth') === 'login' || request('auth') === 'register')
      <script>
        window.__OPEN_AUTH_MODAL__ = @json(request('auth'));
      </script>
    @endif



  <script src="{{ asset('js/app.js') }}" defer></script>
  <script src="{{ asset('js/account.js') }}" defer></script>

  @stack('scripts')
</body>
</html>
