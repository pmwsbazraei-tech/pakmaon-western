<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /** Bayaran yang belum selesai dalam tempoh ini akan diguna semula (elak purchase berganda bila klik dua kali). */
    private const REUSE_MINUTES = 30;

    public function __construct(private PaymentGateway $gateway) {}

    /** Mulakan atau sambung bayaran untuk pesanan. Pulangkan URL checkout, atau null jika sudah dibayar. */
    public function start(Order $order): ?string
    {
        return DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->isPaid() || $order->total_cents <= 0) {
                return null;
            }
            if ($order->isCancelled()) {
                throw new \RuntimeException('Pesanan ini telah dibatalkan.');
            }

            $open = $order->payments()
                ->where('provider', $this->gateway->name())
                ->where('status', 'created')
                ->where('amount_cents', $order->total_cents)
                ->where('created_at', '>=', now()->subMinutes(self::REUSE_MINUTES))
                ->first();
            if ($open && $open->checkout_url) {
                return $open->checkout_url;
            }

            $created = $this->gateway->createPurchase($order, $order->total_cents);

            $order->payments()->create([
                'provider' => $this->gateway->name(),
                'provider_ref' => $created['id'],
                'amount_cents' => $order->total_cents,
                'currency' => 'MYR',
                'status' => 'created',
                'checkout_url' => $created['checkout_url'],
                'raw' => $created['raw'],
            ]);

            $order->update(['payment_status' => 'pending']);
            $order->log('payment_started', 'Pelanggan memulakan Bayar Online.');

            return $created['checkout_url'];
        });
    }

    /**
     * Terima callback/webhook. Badan mentah disahkan, kemudian status disemak semula
     * terus dengan gateway (server-to-server) sebelum apa-apa dikemas kini.
     */
    public function handleNotification(string $rawBody, ?string $signature, string $kind): string
    {
        $payload = json_decode($rawBody, true) ?: [];
        $valid = $this->gateway->verifySignature($rawBody, $signature, $kind);

        try {
            $event = PaymentEvent::create([
                'provider' => $this->gateway->name(),
                'dedupe_key' => hash('sha256', $kind.'|'.$rawBody),
                'provider_ref' => $payload['id'] ?? null,
                'event_type' => $payload['event_type'] ?? $kind,
                'signature_valid' => $valid,
                'payload' => $payload,
            ]);
        } catch (QueryException) {
            return 'duplicate';
        }

        if (! $valid) {
            $event->update(['outcome' => 'invalid_signature']);

            return 'invalid_signature';
        }

        $payment = Payment::where('provider', $this->gateway->name())->where('provider_ref', $payload['id'] ?? '')->first();
        if (! $payment) {
            $event->update(['outcome' => 'unknown_payment']);

            return 'unknown_payment';
        }

        $outcome = $this->sync($payment);
        $event->update(['outcome' => $outcome]);

        return $outcome;
    }

    /** Semak status terus dengan gateway dan kemas kini rekod secara idempotent. */
    public function sync(Payment $payment): string
    {
        if (in_array($payment->status, ['paid', 'refunded'], true)) {
            return 'already_final';
        }

        $remote = $this->gateway->fetchPurchase($payment->provider_ref);

        return DB::transaction(function () use ($payment, $remote) {
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->first();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status === 'paid') {
                return 'already_final';
            }

            if ($remote['status'] === 'paid') {
                $mismatch = [];
                if ($remote['reference'] !== $order->reference) {
                    $mismatch[] = 'rujukan';
                }
                if ($remote['amount_cents'] !== $payment->amount_cents || $payment->amount_cents !== $order->total_cents) {
                    $mismatch[] = 'amaun';
                }
                if (strtoupper($remote['currency']) !== 'MYR') {
                    $mismatch[] = 'mata wang';
                }
                if ($mismatch) {
                    $order->log('payment_mismatch', 'Bayaran '.$payment->provider_ref.' tidak sepadan ('.implode(', ', $mismatch).'). Semak secara manual.');
                    Log::warning('Payment mismatch', ['payment' => $payment->id, 'mismatch' => $mismatch]);

                    return 'mismatch';
                }

                $payment->update(['status' => 'paid', 'paid_at' => now(), 'raw' => $remote['raw']]);

                if ($order->isPaid()) {
                    $order->log('duplicate_payment', 'Bayaran kedua diterima ('.$payment->provider_ref.'). Perlu refund manual.');

                    return 'duplicate_payment';
                }

                $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
                $order->log('payment_paid', 'Bayaran disahkan oleh gateway ('.$payment->provider_ref.').');

                if ($order->isBooking() && $order->booking_status === 'pending' && $order->form->auto_confirm_on_payment) {
                    $order->update(['booking_status' => 'confirmed', 'confirmed_at' => now()]);
                    $order->log('booking_confirmed', 'Disahkan automatik selepas bayaran disahkan.');
                }

                return 'paid';
            }

            if (in_array($remote['status'], ['failed', 'cancelled', 'expired'], true)) {
                $payment->update(['status' => $remote['status'], 'raw' => $remote['raw']]);
                if (! $order->isPaid()) {
                    $order->update(['payment_status' => $remote['status'] === 'expired' ? 'unpaid' : 'failed']);
                    $order->log('payment_'.$remote['status'], 'Bayaran '.$payment->provider_ref.' '.$remote['status'].'. Pelanggan boleh cuba semula.');
                }

                return $remote['status'];
            }

            return 'pending';
        });
    }

    /** Semak semua bayaran terbuka bagi pesanan (digunakan apabila pelanggan kembali dari gateway). */
    public function refresh(Order $order): void
    {
        foreach ($order->payments()->where('status', 'created')->get() as $payment) {
            try {
                $this->sync($payment);
            } catch (\Throwable $e) {
                Log::warning('Payment refresh failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
