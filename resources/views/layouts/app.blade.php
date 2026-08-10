<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $outlet->name ?? 'Pak Maon Western' }} — Sistem Outlet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $navItems = [
        ['dashboard',    '▦', 'Dashboard'],
        ['scan',         '▤', 'Scan Resit'],
        ['staff',        '☺', 'Staff'],
        ['fixed-costs',  '▧', 'Kos Tetap'],
        ['sales',        '▨', 'Sales'],
        ['petty-cash',   '$', 'Petty Cash'],
        ['menu',         '☰', 'Menu Food Cost'],
        ['equipment',    '▬', 'Peralatan'],
        ['capex',        '▩', 'Bajet Buka'],
        ['roi',          '◆', 'ROI & Target'],
        ['report',       '▣', 'Laporan Penuh'],
        ['settings',     '⚙', 'Tetapan'],
    ];
@endphp
<body x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

    {{-- Top bar mobile (hamburger) --}}
    <div class="topbar">
        <div class="burger" @click="sidebarOpen = true" aria-label="Buka menu">☰</div>
        <b>{{ $outlet->name ?? 'Pak Maon Western' }}</b>
    </div>

    {{-- Backdrop off-canvas --}}
    <div class="scrim" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <div class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="brand">
            <div class="logo">PM</div>
            <div><b>Pak Maon</b><br><span>Western Outlet</span></div>
        </div>

        @auth
            @livewire('outlet-switcher')
        @endauth

        <nav class="nav">
            @foreach ($navItems as [$route, $ico, $label])
                <a href="{{ route($route) }}" wire:navigate
                   @click="sidebarOpen = false"
                   @class(['active' => request()->routeIs($route)])>
                    <span class="ico">{{ $ico }}</span><span class="lbl">{{ $label }}</span>
                </a>
            @endforeach

            <form method="POST" action="{{ route('logout') }}" style="margin-top:10px">
                @csrf
                <a href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                    <span class="ico">⎋</span><span class="lbl">Log Keluar</span>
                </a>
            </form>
        </nav>
    </div>

    {{-- Kandungan modul --}}
    <div class="main">
        {{ $slot }}
    </div>

    {{-- Toast global --}}
    <div class="toast" id="toast"
         x-data="{ show: false, msg: '' }"
         x-init="Livewire.on('toast', (e) => { msg = (e.message ?? e[0]?.message ?? e); show = true; setTimeout(() => show = false, 2200); })"
         :class="{ 'show': show }" x-text="msg"></div>

    @livewireScripts
</body>
</html>
