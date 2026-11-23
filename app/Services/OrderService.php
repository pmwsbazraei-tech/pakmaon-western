<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderForm;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private PricingService $pricing, private SlotService $slots) {}

    /**
     * Cipta pesanan/booking secara idempotent.
     * Kunci idempotency yang sama (klik dua kali, retry) memulangkan pesanan sedia ada.
     *
     * @param  array{type:string, outlet_id:?int, name:string, phone:string, email:?string, order_no:?string, booking_date:?string, booking_time:?string, guests:?int, fulfillment:string, delivery_address:?string, notes:?string, items:array}  $data
     */
    public function create(OrderForm $form, array $data, string $idempotencyKey): Order
    {
        if ($existing = Order::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($form, $data, $idempotencyKey) {
            $outlet = $data['outlet_id'] ? Outlet::whereKey($data['outlet_id'])->lockForUpdate()->first() : null;

            // Semak semula selepas kunci, untuk permintaan serentak dengan kunci sama
            if ($existing = Order::where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            $isBooking = $data['type'] === 'booking';
            $quote = $this->pricing->quote($form, $data['items'], $outlet, $data['fulfillment']);

            $errors = [];
            if ($quote['unavailable']) {
                $errors['items'] = 'Item berikut tiada di cawangan ini: '.implode(', ', $quote['unavailable']).'. Sila buang atau tukar.';
            } elseif ($quote['errors']) {
                $errors['items'] = implode(' ', $quote['errors']);
            }

            $hasItems = count($quote['lines']) > 0;
            if (! $hasItems && (! $isBooking || ! $form->allowsTableOnly())) {
                $errors['items'] = $isBooking ? 'Sila pilih sekurang-kurangnya satu menu untuk pre-order.' : 'Cart anda kosong.';
            }

            $bookingAt = null;
            if ($isBooking) {
                $bookingAt = CarbonImmutable::parse($data['booking_date'].' '.$data['booking_time'], config('app.timezone'));
                if ($msg = $this->slots->validate($outlet, $bookingAt, (int) $data['guests'])) {
                    $errors['booking_time'] = $msg;
                }
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            // Kunci baris stok dan tolak stok secara atomik
            foreach ($quote['lines'] as $line) {
                $this->reserveStock($line);
            }

            $order = Order::create([
                'reference' => $this->newReference(),
                'user_id' => $form->user_id,
                'public_token' => Str::random(40),
                'idempotency_key' => $idempotencyKey,
                'order_form_id' => $form->id,
                'outlet_id' => $outlet?->id,
                'type' => $data['type'],
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['email'] ?? null,
                'customer_order_no' => $data['order_no'] ?? null,
                'booking_at' => $bookingAt,
                'guests' => $isBooking ? (int) $data['guests'] : null,
                'fulfillment' => $data['fulfillment'],
                'delivery_address' => $data['fulfillment'] === 'delivery' ? $data['delivery_address'] : null,
                'notes' => $data['notes'] ?? null,
                'prices_include_sst' => $form->prices_include_sst,
                'sst_rate' => $form->sst_rate,
                'subtotal_cents' => $quote['subtotal_cents'],
                'discount_cents' => $quote['discount_cents'],
                'delivery_fee_cents' => $quote['delivery_fee_cents'],
                'sst_added_cents' => $quote['sst_added_cents'],
                'sst_included_cents' => $quote['sst_included_cents'],
                'rounding_cents' => $quote['rounding_cents'],
                'total_cents' => $quote['total_cents'],
                'booking_status' => $isBooking ? 'pending' : null,
                'payment_status' => $quote['total_cents'] > 0 ? 'unpaid' : 'paid',
            ]);

            foreach ($quote['lines'] as $line) {
                $order->items()->create(collect($line)->only([
                    'product_id', 'product_name', 'variant_name', 'options', 'sku', 'unit_price_cents', 'quantity', 'line_total_cents',
                ])->put('product_variant_id', count($line['option_ids'] ?? []) === 1 ? $line['option_ids'][0] : null)->all());

                if ($line['stock'] !== null) {
                    DB::table('stock_movements')->insert([
                        'product_id' => $line['product_id'], 'product_variant_id' => $line['variant_id'],
                        'order_id' => $order->id, 'change' => -$line['quantity'], 'reason' => 'order',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $order->log('created', $isBooking ? 'Booking dicipta, menunggu pengesahan cawangan.' : 'Pesanan dicipta.');

            return $order;
        });
    }

    /** Pulangkan stok apabila pesanan dibatalkan. Hanya sekali bagi setiap pesanan. */
    public function releaseStock(Order $order, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $userId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            if ($order->stock_released) {
                return;
            }
            $moves = DB::table('stock_movements')->where('order_id', $order->id)->where('reason', 'order')->get();
            foreach ($moves as $m) {
                $qty = -$m->change;
                if ($m->product_variant_id) {
                    ProductVariant::whereKey($m->product_variant_id)->whereNotNull('stock')->increment('stock', $qty);
                } else {
                    Product::whereKey($m->product_id)->whereNotNull('stock')->increment('stock', $qty);
                }
                DB::table('stock_movements')->insert([
                    'product_id' => $m->product_id, 'product_variant_id' => $m->product_variant_id, 'order_id' => $order->id,
                    'user_id' => $userId, 'change' => $qty, 'reason' => 'order_release', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $order->update(['stock_released' => true]);
        });
    }

    /** Pesanan yang sudah dibayar online tidak boleh dipadam (rekod kewangan); batal & refund sahaja. */
    public function canDelete(Order $order): bool
    {
        return ! $order->payments()->where('status', 'paid')->exists();
    }

    /** Padam pesanan terus: stok dipulangkan dahulu, kemudian item/bayaran/sejarah turut dipadam. */
    public function delete(Order $order, ?int $userId = null): bool
    {
        if (! $this->canDelete($order)) {
            return false;
        }
        $this->releaseStock($order, $userId);
        $order->delete();

        return true;
    }

    private function reserveStock(array $line): void
    {
        if ($line['stock'] === null) {
            return;
        }
        $model = $line['variant_id']
            ? ProductVariant::whereKey($line['variant_id'])->lockForUpdate()->first()
            : Product::whereKey($line['product_id'])->lockForUpdate()->first();

        if ($model->stock !== null && $model->stock < $line['quantity']) {
            $label = $line['product_name'].($line['variant_name'] ? " ({$line['variant_name']})" : '');
            throw ValidationException::withMessages(['items' => "{$label} baru sahaja habis atau stok tidak mencukupi. Sila kemas kini cart."]);
        }
        // Kemas kini bersyarat: selamat walaupun pangkalan data tidak menyokong kunci baris
        $affected = $model->newQuery()->whereKey($model->id)->where('stock', '>=', $line['quantity'])->decrement('stock', $line['quantity']);
        if ($affected === 0) {
            throw ValidationException::withMessages(['items' => 'Stok tidak mencukupi. Sila kemas kini cart.']);
        }
    }

    private function newReference(): string
    {
        do {
            $ref = 'PMW'.now()->format('ymd').'-'.strtoupper(Str::random(5));
            $ref = strtr($ref, ['0' => '7', 'O' => 'Q', 'I' => 'J', 'L' => 'M']);
        } while (Order::where('reference', $ref)->exists());

        return $ref;
    }
}
