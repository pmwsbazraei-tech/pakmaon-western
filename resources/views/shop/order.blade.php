@extends('layouts.shop')
@section('title', $order->reference.' | Pak Maon Western')
@section('robots', 'noindex,nofollow')

@php
    use App\Models\Order;
    use App\Support\Phone;
    $paid = $order->isPaid();
    $cancelled = $order->isCancelled();
    $last = $lastPayment;
    $failed = ! $paid && $last && in_array($last->status, ['failed', 'cancelled', 'expired']);
    $pendingGateway = ! $paid && $last && $last->status === 'created';
@endphp

@section('content')
<header class="sf-head"><div class="sf-wrap">@include('shop._brand', ['href' => route('form.show', $order->form->slug), 'sub' => $order->isBooking() ? 'Booking meja' : 'Pesanan'])</div></header>

<main class="sf-wrap sf-stack" style="padding-top:18px">
    @if (session('error'))<div class="sf-alert bad">{{ session('error') }}</div>@endif

    <section class="card sf-status">
        @if ($cancelled)
            <div class="ic" style="background:rgba(225,29,72,.12);color:var(--red)">✕</div>
            <h1>{{ $order->isBooking() ? 'Booking dibatalkan' : 'Pesanan dibatalkan' }}</h1>
            <p class="hint">Hubungi cawangan jika anda ada pertanyaan.</p>
        @elseif ($paid)
            <div class="ic" style="background:rgba(22,163,74,.12);color:var(--green)">✓</div>
            <h1>Bayaran diterima</h1>
            <p class="hint">
                @if ($order->isBooking())
                    {{ $order->booking_status === 'confirmed' ? 'Booking anda telah disahkan.' : 'Booking anda menunggu pengesahan cawangan.' }}
                @else
                    Pesanan anda telah diterima dan akan disediakan.
                @endif
            </p>
        @elseif ($failed)
            <div class="ic" style="background:rgba(225,29,72,.12);color:var(--red)">!</div>
            <h1>Bayaran tidak berjaya</h1>
            <p class="hint">Tiada caj dibuat. Anda boleh cuba bayar semula di bawah; nombor rujukan kekal sama.</p>
        @elseif ($pendingGateway && $fromGateway)
            <div class="ic" style="background:rgba(59,130,246,.12);color:var(--blue)">…</div>
            <h1>Menyemak bayaran</h1>
            <p class="hint">Kami belum menerima pengesahan daripada gateway. Muat semula page ini dalam beberapa saat.</p>
        @else
            <div class="ic" style="background:rgba(233,210,30,.25);color:var(--ink)">⏳</div>
            <h1>{{ $order->isBooking() ? 'Menunggu pengesahan cawangan' : 'Pesanan diterima' }}</h1>
            <p class="hint">Lengkapkan langkah di bawah. Simpan nombor rujukan ini.</p>
        @endif
        <div class="hint" style="margin-top:12px">Nombor rujukan</div>
        <div class="sf-ref">{{ $order->reference }}</div>
    </section>

    @if (! $cancelled && ($whatsappAvailable || $onlineAvailable))
        <section class="card sf-stack">
            <h2 style="font-size:17px;font-weight:700">Langkah seterusnya</h2>
            @if ($whatsappAvailable)
                <div>
                    <a class="btn wa block" href="{{ route('order.whatsapp', ['reference' => $order->reference, 't' => $order->public_token]) }}" target="_blank" rel="noopener">
                        Hantar {{ $order->isBooking() ? 'Booking' : 'Pesanan' }} ke WhatsApp
                    </a>
                    <p class="hint">
                        WhatsApp {{ $order->outlet->name }} akan dibuka dengan mesej siap diisi. Tekan <b>Send</b> dalam WhatsApp untuk menghantarnya.
                        @if ($order->whatsapp_opened_at) Pautan WhatsApp telah dibuka; cawangan akan mengesahkan selepas menerima mesej anda. @endif
                    </p>
                </div>
            @endif
            @if ($onlineAvailable)
                <form method="POST" action="{{ route('order.pay', ['reference' => $order->reference, 't' => $order->public_token]) }}" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <button class="btn block" :disabled="busy" type="submit">
                        <span x-text="busy ? 'Membuka gateway…' : '{{ $failed ? 'Cuba Bayar Semula' : 'Bayar Online' }} · {{ rm($order->total_cents / 100) }}'"></span>
                    </button>
                    <p class="hint">FPX, kad dan e-wallet melalui CHIP. Nombor rujukan yang sama digunakan; pesanan tidak akan berganda.</p>
                </form>
            @endif
        </section>
    @endif

    <section class="card">
        <h2 style="font-size:17px;font-weight:700;margin-bottom:10px">Ringkasan</h2>
        <dl class="sf-kv">
            <dt>Nama</dt><dd>{{ $order->customer_name }}</dd>
            @if ($order->customer_order_no)<dt>No. order</dt><dd>{{ $order->customer_order_no }}</dd>@endif
            <dt>Telefon</dt><dd>{{ Phone::display($order->customer_phone) }}</dd>
            <dt>Cawangan</dt><dd>{{ $order->outlet?->name }}</dd>
            @if ($order->isBooking())
                <dt>Tarikh & masa</dt><dd>{{ $order->booking_at->translatedFormat('l, j F Y') }}, {{ $order->booking_at->format('g:i A') }}</dd>
                <dt>Tetamu</dt><dd>{{ $order->guests }} orang</dd>
            @else
                <dt>Cara terima</dt><dd>{{ Order::FULFILLMENTS[$order->fulfillment] }}</dd>
                @if ($order->delivery_address)<dt>Alamat</dt><dd>{{ $order->delivery_address }}</dd>@endif
            @endif
            @if ($order->notes)<dt>Nota</dt><dd>{{ $order->notes }}</dd>@endif
            <dt>Status bayaran</dt><dd>{{ Order::PAYMENT_STATUSES[$order->payment_status] }}</dd>
        </dl>

        <div style="margin-top:14px">
            @forelse ($order->items as $i)
                <div class="sf-line">
                    <div><div class="nm">{{ $i->quantity }} × {{ $i->product_name }}</div>@if ($i->variant_name)<div class="mt">{{ $i->variant_name }}</div>@endif</div>
                    <div class="sf-price" style="text-align:right">@sen($i->line_total_cents)</div>
                </div>
            @empty
                <p class="hint">Booking meja sahaja, tiada pre-order menu.</p>
            @endforelse
        </div>
        <div class="sf-totals" style="margin-top:10px">
            <div class="sf-between"><span>Subtotal</span><span>@sen($order->subtotal_cents)</span></div>
            @if ($order->discount_cents)<div class="sf-between"><span>Diskaun</span><span>-@sen($order->discount_cents)</span></div>@endif
            @if ($order->delivery_fee_cents)<div class="sf-between"><span>Caj penghantaran</span><span>@sen($order->delivery_fee_cents)</span></div>@endif
            @if ($order->sst_added_cents)<div class="sf-between"><span>SST</span><span>@sen($order->sst_added_cents)</span></div>@endif
            <div class="sf-between grand"><span>Jumlah</span><span>@sen($order->total_cents)</span></div>
            @if ($order->prices_include_sst && $order->sst_included_cents)
                <div class="hint">Termasuk SST {{ rtrim(rtrim($order->sst_rate, '0'), '.') }}%: @sen($order->sst_included_cents)</div>
            @endif
        </div>
    </section>

    @if ($order->isBooking() && $order->form->booking_notice)
        <div class="sf-notice">{{ $order->form->booking_notice }}</div>
    @endif

    <a class="btn sec block" href="{{ route('form.show', $order->form->slug) }}">Kembali ke menu</a>
</main>
@endsection
