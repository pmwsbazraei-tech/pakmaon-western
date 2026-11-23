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
            : ($order->form->onlinePaymentEnabled() && $order->total_cents > 0 ? 'Pautan bayaran/invois: '.$order->publicUrl() : '');

        $rate = rtrim(rtrim((string) $order->sst_rate, '0'), '.');
        $sstLine = match (true) {
            $order->sst_added_cents > 0 => "SST {$rate}%: ".Money::format($order->sst_added_cents),
            $order->prices_include_sst && $order->sst_included_cents > 0 => "SST {$rate}% (termasuk dalam harga): ".Money::format($order->sst_included_cents),
            default => '',
        };
        $roundingLine = $order->rounding_cents ? 'Rounding: '.Money::format($order->rounding_cents) : '';

        // Ringkasan bil: baris yang kosong (tiada diskaun/SST/rounding) tidak dipaparkan
        $bill = implode("\n", array_filter([
            'Subtotal: '.Money::format($order->subtotal_cents),
            $order->discount_cents ? 'Diskaun: -'.Money::format($order->discount_cents) : '',
            $order->delivery_fee_cents ? 'Caj penghantaran: '.Money::format($order->delivery_fee_cents) : '',
            $sstLine,
            $roundingLine,
            'Jumlah akhir: '.Money::format($order->total_cents),
        ]));

        $vars = [
            '{subtotal}' => Money::format($order->subtotal_cents),
            '{sst}' => Money::format($order->sst_added_cents ?: $order->sst_included_cents),
            '{rounding}' => Money::format($order->rounding_cents),
            '{bill}' => $bill,
            '{reference}' => $order->reference,
            '{name}' => $order->customer_name,
            '{phone}' => Phone::display($order->customer_phone),
            '{email}' => $order->customer_email ?: '-',
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
            '{fulfillment}' => $order->showsFulfillment() ? (Order::FULFILLMENTS[$order->fulfillment] ?? $order->fulfillment) : '',
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
