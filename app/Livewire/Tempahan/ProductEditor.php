<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Produk jualan online: harga, promosi, SKU, stok, had belian, variasi, ketersediaan outlet. */
#[Layout('layouts.app')]
class ProductEditor extends Component
{
    use InteractsWithShop;

    public ?Product $product = null;

    public array $p = [];

    public array $variants = [];

    public array $unavailable = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            abort_unless($product->user_id === $this->shopOwner()->id, 404);
            $this->product = $product;
        }
        $m = $this->product ?? new Product;
        $c = fn ($v) => $v !== null ? number_format($v / 100, 2, '.', '') : '';

        $this->p = [
            'name' => $m->name ?? '', 'sku' => $m->sku ?? '', 'product_category_id' => (string) ($m->product_category_id ?? ''),
            'new_category' => '', 'description' => $m->description ?? '', 'image_url' => $m->image_url ?? '',
            'menu_id' => (string) ($m->menu_id ?? ''), 'price' => $c($m->price_cents), 'promo_price' => $c($m->promo_price_cents),
            'stock' => $m->stock !== null ? (string) $m->stock : '', 'max_per_order' => (string) ($m->max_per_order ?? ''),
            'sort' => (string) ($m->sort ?? 0), 'is_hidden' => (bool) $m->is_hidden,
        ];
        $this->variants = $this->product ? $this->product->variants()->where('is_active', true)->get()->map(fn ($v) => [
            'id' => $v->id, 'name' => $v->name, 'sku' => $v->sku ?? '', 'price' => $c($v->price_cents),
            'promo_price' => $c($v->promo_price_cents), 'stock' => $v->stock !== null ? (string) $v->stock : '',
            'max_per_order' => (string) ($v->max_per_order ?? ''),
        ])->all() : [];
        $this->unavailable = $this->product
            ? DB::table('outlet_product')->where('product_id', $this->product->id)->where('is_available', false)->pluck('outlet_id')->map(fn ($i) => (string) $i)->all()
            : [];
    }

    public function addVariant(): void
    {
        $this->variants[] = ['id' => null, 'name' => '', 'sku' => '', 'price' => '', 'promo_price' => '', 'stock' => '', 'max_per_order' => ''];
    }

    public function removeVariant(int $i): void
    {
        unset($this->variants[$i]);
        $this->variants = array_values($this->variants);
    }

    public function save()
    {
        $owner = $this->shopOwner();
        $this->validate([
            'p.name' => ['required', 'string', 'max:150'],
            'p.sku' => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->where('user_id', $owner->id)->ignore($this->product?->id)],
            'p.product_category_id' => ['nullable', Rule::in(ProductCategory::where('user_id', $owner->id)->pluck('id')->map(fn ($i) => (string) $i)->all())],
            'p.menu_id' => ['nullable', Rule::in(Menu::whereIn('outlet_id', $owner->outlets()->pluck('id'))->pluck('id')->map(fn ($i) => (string) $i)->all())],
            'p.image_url' => ['nullable', 'url', 'max:500'],
            'p.price' => ['nullable', 'numeric', 'min:0'],
            'p.promo_price' => ['nullable', 'numeric', 'min:0'],
            'p.stock' => ['nullable', 'integer', 'min:0'],
            'p.max_per_order' => ['nullable', 'integer', 'min:1'],
            'p.sort' => ['nullable', 'integer'],
            'variants.*.name' => ['nullable', 'string', 'max:120'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.promo_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.max_per_order' => ['nullable', 'integer', 'min:1'],
            'unavailable.*' => [Rule::in($owner->outlets()->pluck('id')->map(fn ($i) => (string) $i)->all())],
        ], [], ['p.name' => 'nama', 'p.price' => 'harga']);

        $variants = collect($this->variants)->filter(fn ($v) => trim($v['name'] ?? '') !== '')->values();
        $hasVariants = $variants->isNotEmpty();
        if (! $hasVariants && $this->p['price'] === '') {
            return $this->addError('p.price', 'Isi harga, atau tambah sekurang-kurangnya satu variasi.');
        }
        if ($variants->contains(fn ($v) => ($v['price'] ?? '') === '')) {
            return $this->addError('variants', 'Setiap variasi perlu ada harga.');
        }

        $product = DB::transaction(function () use ($owner, $variants, $hasVariants) {
            $categoryId = $this->p['product_category_id'] ?: null;
            if (trim($this->p['new_category']) !== '') {
                $categoryId = ProductCategory::firstOrCreate(
                    ['user_id' => $owner->id, 'name' => trim($this->p['new_category'])],
                    ['sort' => (ProductCategory::where('user_id', $owner->id)->max('sort') ?? 0) + 1],
                )->id;
            }
            $nul = fn ($v) => $v === '' ? null : $v;

            $product = $this->product ?? new Product(['user_id' => $owner->id]);
            $oldStock = $product->stock;
            $product->fill([
                'name' => $this->p['name'], 'sku' => $nul($this->p['sku']), 'product_category_id' => $categoryId,
                'menu_id' => $nul($this->p['menu_id']), 'description' => $nul($this->p['description']), 'image_url' => $nul($this->p['image_url']),
                'has_variants' => $hasVariants,
                'price_cents' => $hasVariants ? null : Money::toCents($this->p['price']),
                'promo_price_cents' => $hasVariants ? null : Money::toCents($this->p['promo_price']),
                'stock' => $hasVariants ? null : $nul($this->p['stock']),
                'max_per_order' => $nul($this->p['max_per_order']), 'sort' => (int) ($this->p['sort'] ?: 0),
                'is_hidden' => (bool) $this->p['is_hidden'],
            ])->save();
            $this->logStock($product->id, null, $oldStock, $product->stock);

            $keep = [];
            foreach ($variants as $i => $v) {
                $variant = ! empty($v['id']) ? $product->variants()->find($v['id']) : null;
                $old = $variant?->stock;
                $variant ??= $product->variants()->make();
                $variant->fill([
                    'name' => $v['name'], 'sku' => $nul($v['sku'] ?? ''), 'price_cents' => Money::toCents($v['price']),
                    'promo_price_cents' => Money::toCents($v['promo_price'] ?? ''), 'stock' => $nul($v['stock'] ?? ''),
                    'max_per_order' => $nul($v['max_per_order'] ?? ''), 'is_active' => true, 'sort' => $i,
                ])->save();
                $this->logStock($product->id, $variant->id, $old, $variant->stock);
                $keep[] = $variant->id;
            }
            // Variasi dibuang dinyahaktifkan (bukan dipadam) supaya sejarah pesanan kekal
            $product->variants()->whereNotIn('id', $keep)->update(['is_active' => false]);

            DB::table('outlet_product')->where('product_id', $product->id)->delete();
            foreach ($this->unavailable as $oid) {
                DB::table('outlet_product')->insert(['outlet_id' => (int) $oid, 'product_id' => $product->id, 'is_available' => false]);
            }

            return $product;
        });

        if (! $this->product) {
            return $this->redirectRoute('tempahan.products.edit', $product, navigate: true);
        }
        $this->mount($product->fresh());
        $this->dispatch('toast', message: 'Produk disimpan.');
    }

    private function logStock(int $productId, ?int $variantId, $old, $new): void
    {
        if ($new === null || (string) $old === (string) $new) {
            return;
        }
        DB::table('stock_movements')->insert([
            'product_id' => $productId, 'product_variant_id' => $variantId, 'user_id' => auth()->id(),
            'change' => (int) $new - (int) $old, 'reason' => 'adjustment', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function render()
    {
        $owner = $this->shopOwner();

        return view('livewire.tempahan.product-editor', [
            'categories' => ProductCategory::where('user_id', $owner->id)->orderBy('sort')->get(),
            'outlets' => $owner->outlets()->orderBy('name')->get(),
            'menus' => Menu::whereIn('outlet_id', $owner->outlets()->pluck('id'))->with('outlet')->orderBy('name')->get(),
        ]);
    }
}
