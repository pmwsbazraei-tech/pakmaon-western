<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Money;
use App\Support\Phone;

class WhatsAppService
{
    public function message(Order $order): string
    {
        $order->loadMissing('items', 'outlet', 'form');

        $items = $order->items->map(fn ($i) => "- {$i->quantity} x {$i->product_name}".($i->variant_name ? " ({$i->variant_name})" : '').' = '.Money::format($i->line_total_cents))->implode("\n");

        $paymentLine = $order->isPaid()
            ? 'Status bayaran: Dibayar'
            : ($order->form->action_online && $order->total_cents > 0 ? 'Pautan bayaran/invois: '.$order->publicUrl() : '');

        $vars = [
            '{reference}' => $order->reference,
            '{name}' => $order->customer_name,
            '{phone}' => Phone::display($order->customer_phone),
            '{order_no}' => $order->customer_order_no ?: '-',
            '{OrderNo}' => $order->customer_order_no ?: '-',   // alias
            '{outlet}' => $order->outlet?->name ?? '-',
            '{branch}' => $order->outlet?->name ?? '-',   // alias lama
            '{date}' => $order->booking_at?->translatedFormat('l, j F Y') ?? '-',
            '{time}' => $order->booking_at?->format('g:i A') ?? '-',
            '{guests}' => (string) ($order->guests ?? '-'),
            '{items}' => $items !== '' ? $items : '(Booking meja sahaja)',
            '{total}' => Money::format($order->total_cents),
            '{notes}' => $order->notes ?: '-',
            '{fulfillment}' => Order::FULFILLMENTS[$order->fulfillment] ?? $order->fulfillment,
            '{address}' => $order->delivery_address ?: '-',
            '{payment_link}' => $paymentLine,
        ];

        return trim(strtr($order->form->template(), $vars));
    }

    public function url(Order $order): ?string
    {
        $number = $order->outlet?->whatsapp;
        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($this->message($order));
    }
}
