<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Gateway simulasi untuk pembangunan tempatan dan ujian sahaja.
 * Tiada wang sebenar. Status disimpan dalam payments.raw.fake_status.
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createPurchase(Order $order, int $amountCents): array
    {
        $id = 'fake_'.Str::lower(Str::random(16));

        return [
            'id' => $id,
            'checkout_url' => route('payments.fake.show', $id),
            'raw' => ['fake_status' => 'created', 'reference' => $order->reference, 'total' => $amountCents, 'currency' => 'MYR'],
        ];
    }

    public function fetchPurchase(string $id): array
    {
        $payment = Payment::where('provider_ref', $id)->firstOrFail();
        $raw = $payment->raw ?? [];

        return [
            'status' => $raw['fake_status'] ?? 'created',
            'amount_cents' => (int) ($raw['total'] ?? 0),
            'currency' => $raw['currency'] ?? 'MYR',
            'reference' => $raw['reference'] ?? null,
            'raw' => $raw,
        ];
    }

    public function verifySignature(string $rawBody, ?string $signature, string $kind): bool
    {
        return hash_equals(hash_hmac('sha256', $rawBody, (string) config('app.key')), (string) $signature);
    }
}
