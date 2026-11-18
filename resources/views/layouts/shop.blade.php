<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pak Maon Western')</title>
    <meta name="description" content="@yield('description', 'Tempah meja, pre-order menu dan pesan dari Pak Maon Western.')">
    <meta property="og:title" content="@yield('title', 'Pak Maon Western')">
    <meta property="og:description" content="@yield('description', 'Tempah meja, pre-order menu dan pesan dari Pak Maon Western.')">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta name="theme-color" content="#d23a1f">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=barlow:400,500,600,700|barlow-condensed:600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="sf">
    @yield('content')
    <footer class="sf-foot">© {{ now()->year }} Pak Maon Western</footer>
    {{-- Alpine.js dibundle bersama Livewire 3 (sama seperti modul lain) --}}
    @livewireScripts
    @stack('scripts')
</body>
</html>
