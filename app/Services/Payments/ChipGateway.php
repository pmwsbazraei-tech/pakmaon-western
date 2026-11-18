<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * CHIP Collect (https://gate.chip-in.asia/api/v1/).
 * Ujian atau live ditentukan oleh API key. Amaun dalam sen.
 */
class ChipGateway implements PaymentGateway
{
    private const BASE = 'https://gate.chip-in.asia/api/v1/';

    public function __construct(private string $brandId, private string $secretKey, private ?string $webhookPublicKey) {}

    public function name(): string
    {
        return 'chip';
    }

    public function createPurchase(Order $order, int $amountCents): array
    {
        $order->loadMissing('items');
        $res = $this->http()->post('purchases/', [
            'brand_id' => $this->brandId,
            'reference' => $order->reference,
            'client' => array_filter([
                'full_name' => $order->customer_name,
                'phone' => '+'.$order->customer_phone,
                'email' => $order->customer_email ?: 'pelanggan+'.strtolower($order->reference).'@pakmaonwestern.invalid',
            ]),
            'purchase' => [
                'currency' => 'MYR',
                // Satu baris dengan jumlah akhir: harga sudah termasuk SST; tiada cukai ditambah oleh gateway.
                'products' => [[
                    'name' => 'Pak Maon Western '.$order->reference,
                    'price' => $amountCents,
                    'quantity' => '1',
                ]],
            ],
            'success_callback' => route('payments.chip.callback'),
            'success_redirect' => route('order.show', ['reference' => $order->reference, 't' => $order->public_token, 'from' => 'gateway']),
            'failure_redirect' => route('order.show', ['reference' => $order->reference, 't' => $order->public_token, 'from' => 'gateway']),
            'cancel_redirect' => route('order.show', ['reference' => $order->reference, 't' => $order->public_token, 'from' => 'gateway']),
        ]);

        if (! $res->successful()) {
            throw new RuntimeException('CHIP: gagal mencipta purchase ('.$res->status().') '.$res->body());
        }

        return ['id' => $res->json('id'), 'checkout_url' => $res->json('checkout_url'), 'raw' => $res->json()];
    }

    public function fetchPurchase(string $id): array
    {
        $res = $this->http()->get('purchases/'.rawurlencode($id).'/');
        if (! $res->successful()) {
            throw new RuntimeException('CHIP: gagal menyemak purchase ('.$res->status().')');
        }
        $data = $res->json();

        $status = match ($data['status'] ?? null) {
            'paid', 'captured' => 'paid',
            'error' => 'failed',
            'cancelled' => 'cancelled',
            'expired' => 'expired',
            'refunded' => 'refunded',
            default => 'created',
        };

        return [
            'status' => $status,
            'amount_cents' => (int) ($data['purchase']['total'] ?? 0),
            'currency' => (string) ($data['purchase']['currency'] ?? ''),
            'reference' => $data['reference'] ?? null,
            'raw' => $data,
        ];
    }

    public function verifySignature(string $rawBody, ?string $signature, string $kind): bool
    {
        if (! $signature) {
            return false;
        }
        $pem = $kind === 'webhook' ? $this->webhookPublicKey : $this->callbackPublicKey();
        if (! $pem) {
            return false;
        }
        $key = openssl_pkey_get_public($pem);
        if ($key === false) {
            return false;
        }

        return openssl_verify($rawBody, base64_decode($signature, true) ?: '', $key, OPENSSL_ALGO_SHA256) === 1;
    }

    private function callbackPublicKey(): ?string
    {
        return Cache::remember('chip.public_key', now()->addDay(), function () {
            $res = $this->http()->get('public_key/');

            return $res->successful() ? (is_string($res->json()) ? $res->json() : $res->body()) : null;
        });
    }

    private function http()
    {
        return Http::baseUrl(self::BASE)->withToken($this->secretKey)->acceptJson()->asJson()->timeout(20);
    }
}
