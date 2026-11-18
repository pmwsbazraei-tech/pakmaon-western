@php
    $tone = fn ($v) => match ($v) {
        'paid', 'confirmed', 'completed', 'ready' => 'ok',
        'pending', 'unpaid', 'new', 'preparing' => 'warn',
        'failed', 'cancelled', 'no_show', 'expired', 'refunded' => 'bad',
        default => '',
    };
@endphp
<span style="display:inline-flex;gap:5px;flex-wrap:wrap">
    @if ($o->isBooking())<span class="badge {{ $tone($o->booking_status) }}">{{ \App\Models\Order::BOOKING_STATUSES[$o->booking_status] ?? $o->booking_status }}</span>@endif
    <span class="badge {{ $tone($o->payment_status) }}">{{ \App\Models\Order::PAYMENT_STATUSES[$o->payment_status] ?? $o->payment_status }}</span>
    @unless ($o->isBooking())<span class="badge {{ $tone($o->fulfillment_status) }}">{{ \App\Models\Order::FULFILLMENT_STATUSES[$o->fulfillment_status] }}</span>@endunless
    @if ($o->whatsapp_opened_at && $o->booking_status === 'pending')<span class="chip" title="Pelanggan membuka pautan WhatsApp. Mesej belum tentu dihantar.">WA dibuka</span>@endif
</span>
