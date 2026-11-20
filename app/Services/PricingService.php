<?php

namespace App\Services;

use App\Models\OrderForm;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;

/**
 * Semua harga dikira di server, dalam sen (MYR). Pelayar hanya hantar ID + kuantiti.
 *
 * SST: jika form ditetapkan "harga termasuk SST", harga paparan ialah harga jualan akhir.
 * SST tidak ditambah lagi; bahagian SST dalam harga dikira untuk maklumat sahaja.
 */
class PricingService
{
    /**
     * @param  array<int, array{product_id:int, variant_id?:int|null, quantity:int}>  $lines
     * @return array{lines: array, subtotal_cents:int, discount_cents:int, delivery_fee_cents:int, sst_added_cents:int, sst_included_cents:int, rounding_cents:int, total_cents:int, errors: array<int,string>, unavailable: array<int,string>}
     */
    public function quote(OrderForm $form, array $lines, ?Outlet $outlet, string $fulfillment): array
    {
        $errors = [];
        $unavailable = [];
        $priced = [];

        $allowedIds = $form->products()->pluck('products.id')->all();
        $outletUnavailable = $outlet ? $outlet->unavailableProductIds() : [];

        // Gabungkan baris yang sama (produk + variasi)
        $merged = [];
        foreach ($lines as $line) {
            $pid = (int) ($line['product_id'] ?? 0);
            $vid = isset($line['variant_id']) && $line['variant_id'] !== null && $line['variant_id'] !== '' ? (int) $line['variant_id'] : null;
            $qty = (int) ($line['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            $key = $pid.':'.($vid ?? 0);
            $merged[$key] = ['product_id' => $pid, 'variant_id' => $vid, 'quantity' => ($merged[$key]['quantity'] ?? 0) + $qty];
        }

        $products = Product::with('activeVariants')->whereIn('id', array_column($merged, 'product_id'))->get()->keyBy('id');

        foreach ($merged as $line) {
            /** @var Product|null $product */
            $product = $products->get($line['product_id']);
            if (! $product || $product->is_hidden || ! in_array($product->id, $allowedIds, true)) {
                $errors[] = 'Satu item dalam cart tidak lagi tersedia dan telah dibuang.';

                continue;
            }

            $variant = null;
            if ($product->has_variants) {
                /** @var ProductVariant|null $variant */
                $variant = $product->activeVariants->firstWhere('id', $line['variant_id']);
                if (! $variant) {
                    $errors[] = "Sila pilih variasi untuk {$product->name}.";

                    continue;
                }
                $unit = $variant->effectivePrice();
                $stock = $variant->stock;
                $max = $variant->max_per_order ?? $product->max_per_order;
            } else {
                $unit = $product->effectivePrice() ?? 0;
                $stock = $product->stock;
                $max = $product->max_per_order;
            }

            $label = $product->name.($variant ? " ({$variant->name})" : '');

            if (in_array($product->id, $outletUnavailable, true)) {
                $unavailable[] = $label;

                continue;
            }

            $qty = $line['quantity'];
            if ($stock !== null && $stock <= 0) {
                $errors[] = "{$label} sudah habis stok.";

                continue;
            }
            if ($stock !== null && $qty > $stock) {
                $errors[] = "{$label}: hanya {$stock} lagi dalam stok.";
                $qty = $stock;
            }
            if ($max !== null && $qty > $max) {
                $errors[] = "{$label}: had belian {$max} bagi setiap pesanan.";
                $qty = $max;
            }

            $priced[] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'product_name' => $product->name,
                'variant_name' => $variant?->name,
                'sku' => $variant?->sku ?? $product->sku,
                'unit_price_cents' => $unit,
                'quantity' => $qty,
                'line_total_cents' => $unit * $qty,
                'stock' => $stock,
            ];
        }

        $subtotal = array_sum(array_column($priced, 'line_total_cents'));
        $discount = 0; // kupon: Fasa 2

        $delivery = 0;
        if ($fulfillment === 'delivery') {
            $delivery = $outlet && $outlet->delivery_fee_cents > 0 ? $outlet->delivery_fee_cents : $form->delivery_fee_cents;
        }

        $taxable = max(0, $subtotal - $discount);
        $rate = (float) $form->sst_rate;
        if ($form->prices_include_sst) {
            $sstAdded = 0;
            $sstIncluded = $rate > 0 ? (int) round($taxable * $rate / (100 + $rate)) : 0;
        } else {
            $sstAdded = (int) round($taxable * $rate / 100);
            $sstIncluded = 0;
        }

        // Bundar ke 5 sen terdekat (amalan pembundaran Malaysia): 1,2 turun; 3,4 naik; 6,7 turun; 8,9 naik
        $beforeRounding = $taxable + $sstAdded + $delivery;
        $total = (int) (round($beforeRounding / 5) * 5);
        $rounding = $total - $beforeRounding;

        if ($subtotal > 0 && $form->min_order_cents && $subtotal < $form->min_order_cents) {
            $errors[] = 'Belian minimum ialah '.Money::format($form->min_order_cents).'.';
        }
        if ($form->max_order_cents && $subtotal > $form->max_order_cents) {
            $errors[] = 'Belian maksimum ialah '.Money::format($form->max_order_cents).'.';
        }

        return [
            'lines' => $priced,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'delivery_fee_cents' => $delivery,
            'sst_added_cents' => $sstAdded,
            'sst_included_cents' => $sstIncluded,
            'rounding_cents' => $rounding,
            'total_cents' => $total,
            'errors' => array_values(array_unique($errors)),
            'unavailable' => $unavailable,
        ];
    }
}
