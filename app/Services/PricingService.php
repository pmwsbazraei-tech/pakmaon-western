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
     * Harga baris = harga produk + harga semua add-on yang dipilih.
     *
     * @param  array<int, array{product_id:int, options?:array<int,int>, variant_id?:int|null, quantity:int}>  $lines
     * @return array{lines: array, subtotal_cents:int, discount_cents:int, delivery_fee_cents:int, sst_added_cents:int, sst_included_cents:int, rounding_cents:int, total_cents:int, errors: array<int,string>, unavailable: array<int,string>}
     */
    public function quote(OrderForm $form, array $lines, ?Outlet $outlet, string $fulfillment): array
    {
        $errors = [];
        $unavailable = [];
        $priced = [];

        $allowedIds = $form->products()->pluck('products.id')->all();
        $hiddenVariants = $form->exists ? $form->hiddenVariantIds() : [];
        $outletUnavailable = $outlet ? $outlet->unavailableProductIds() : [];

        // Gabungkan baris yang sama (produk + set add-on yang sama)
        $merged = [];
        foreach ($lines as $line) {
            $pid = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            $opts = collect($line['options'] ?? [])->map(fn ($i) => (int) $i)->filter()->unique()->sort()->values()->all();
            // Cart lama: satu variant_id
            if ($opts === [] && ! empty($line['variant_id'])) {
                $opts = [(int) $line['variant_id']];
            }
            $key = $pid.':'.implode(',', $opts);
            $merged[$key] = ['product_id' => $pid, 'options' => $opts, 'quantity' => ($merged[$key]['quantity'] ?? 0) + $qty];
        }

        $products = Product::with('activeVariants.variant.group')->whereIn('id', array_column($merged, 'product_id'))->get()->keyBy('id');

        foreach ($merged as $line) {
            /** @var Product|null $product */
            $product = $products->get($line['product_id']);
            if (! $product || $product->is_hidden || ! in_array($product->id, $allowedIds, true)) {
                $errors[] = __('Satu item dalam cart tidak lagi tersedia dan telah dibuang.');

                continue;
            }

            // Add-on: setiap pilihan mesti milik produk ini dan tidak disorok pada landing page
            $available = $product->activeVariants->reject(fn ($v) => in_array($v->id, $hiddenVariants, true))->keyBy('id');
            $chosen = collect($line['options'])->map(fn ($id) => $available->get($id));
            if ($chosen->contains(null)) {
                $errors[] = __('Satu item dalam cart tidak lagi tersedia dan telah dibuang.');

                continue;
            }

            // Peraturan setiap group: Required (sekurang-kurangnya 1) & maksimum pilihan
            $ruleError = null;
            foreach ($available->groupBy(fn ($v) => $v->variant?->variant_group_id ?? 0) as $groupId => $options) {
                $group = $options->first()->variant?->group;
                $picked = $chosen->filter(fn ($v) => ($v->variant?->variant_group_id ?? 0) === $groupId)->count();
                $required = $group ? $group->is_required : true;
                $max = $group ? $group->effectiveMax($options->count()) : 1;
                $name = $group?->name ?? __('pilihan');
                if ($required && $picked === 0) {
                    $ruleError = __('Sila pilih :group untuk :product.', ['group' => $name, 'product' => $product->name]);
                    break;
                }
                if ($picked > $max) {
                    $ruleError = __(':product: pilih maksimum :max :group.', ['product' => $product->name, 'max' => $max, 'group' => $name]);
                    break;
                }
            }
            if ($ruleError) {
                $errors[] = $ruleError;

                continue;
            }

            $chosen = $chosen->sortBy('sort')->values();
            $unit = ($product->effectivePrice() ?? 0) + $chosen->sum(fn (ProductVariant $v) => $v->effectivePrice());
            $stock = $product->stock;
            $optionNames = $chosen->pluck('name')->join(', ');

            $label = $product->name.($optionNames !== '' ? " ({$optionNames})" : '');

            if (in_array($product->id, $outletUnavailable, true)) {
                $unavailable[] = $label;

                continue;
            }

            $qty = $line['quantity'];
            if ($stock !== null && $stock <= 0) {
                $errors[] = __(':item sudah habis stok.', ['item' => $label]);

                continue;
            }
            if ($stock !== null && $qty > $stock) {
                $errors[] = __(':item: hanya :count lagi dalam stok.', ['item' => $label, 'count' => $stock]);
                $qty = $stock;
            }
            $priced[] = [
                'product_id' => $product->id,
                // Stok dikira pada produk; variant tiada stok sendiri
                'variant_id' => null,
                'option_ids' => $chosen->pluck('id')->all(),
                'options' => $chosen->map(fn ($v) => [
                    'id' => $v->id, 'group' => $v->variant?->group?->name, 'name' => $v->name, 'price_cents' => $v->effectivePrice(),
                ])->all(),
                'product_name' => $product->name,
                'variant_name' => $optionNames !== '' ? $optionNames : null,
                'sku' => $product->sku,
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
            $errors[] = __('Belian minimum ialah :amount.', ['amount' => Money::format($form->min_order_cents)]);
        }
        if ($form->max_order_cents && $subtotal > $form->max_order_cents) {
            $errors[] = __('Belian maksimum ialah :amount.', ['amount' => Money::format($form->max_order_cents)]);
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
