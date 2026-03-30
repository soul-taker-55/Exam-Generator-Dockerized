<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  {{-- قاعدة عامة + صفحة الضيف --}}
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('css/pages/auth.css') }}">
  <title>@yield('title','Auth')</title>
</head>
<body class="auth-bg">
  @yield('content')
  {{-- scripts العامّة إن احتجنا لاحقاً --}}
  @stack('scripts')
</body>
</html>
