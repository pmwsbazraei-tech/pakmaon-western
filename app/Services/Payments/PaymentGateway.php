<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    public function name(): string;

    /** @return array{id:string, checkout_url:string, raw:array} */
    public function createPurchase(Order $order, int $amountCents): array;

    /**
     * Semakan server-to-server. Status dinormalkan: created | paid | failed | cancelled | expired.
     *
     * @return array{status:string, amount_cents:int, currency:string, reference:?string, raw:array}
     */
    public function fetchPurchase(string $id): array;

    /** Sahkan tandatangan callback/webhook terhadap badan mentah permintaan. */
    public function verifySignature(string $rawBody, ?string $signature, string $kind): bool;
}
