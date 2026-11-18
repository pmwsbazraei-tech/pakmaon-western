@extends('layouts.shop')
@section('title', $form->title)
@section('robots', 'noindex')
@section('content')
<header class="sf-head"><div class="sf-wrap">@include('shop._brand')</div></header>
<main class="sf-wrap" style="padding:60px 0">
    <div class="card sf-status">
        <div class="ic" style="background:rgba(184,144,31,.15);color:var(--yellow)">⏸</div>
        <h1>Ditutup sementara</h1>
        <p style="color:var(--muted);margin-top:6px">{{ $form->maintenance_message ?: 'Page ini sedang dikemas kini. Sila cuba sebentar lagi.' }}</p>
    </div>
</main>
@endsection
