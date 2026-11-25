<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Menu;
use App\Models\OrderForm;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VariantGroup;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Produk jualan online: harga, promosi, SKU, stok, had belian, variasi, ketersediaan outlet. */
#[Layout('layouts.app')]
class ProductEditor extends Component
{
    use InteractsWithShop, WithFileUploads;

    /** Gambar produk yang dimuat naik (disimpan ke storage, dipapar di halaman pelanggan). */
    public $photo = null;

    public ?Product $product = null;

    public array $p = [];

    public array $groupIds = [];

    /** Outlet yang menjual produk ini (ditick). Disimpan sebagai senarai outlet yang TIDAK menjual. */
    public array $availableOutlets = [];

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
            'menu_id' => (string) ($m->menu_id ?? ''), 'price' => $c($m->price_cents ?? ($m->has_variants ? 0 : null)), 'promo_price' => $c($m->promo_price_cents),
            'stock' => $m->stock !== null ? (string) $m->stock : '',
            'is_hidden' => (bool) $m->is_hidden,
        ];
        // Variant group yang ditick
        $this->groupIds = $this->product
            ? $this->product->variantGroups()->where('is_active', true)->pluck('variant_groups.id')->map(fn ($i) => (string) $i)->all()
            : [];
        $unavailable = $this->product
            ? DB::table('outlet_product')->where('product_id', $this->product->id)->where('is_available', false)->pluck('outlet_id')->all()
            : [];
        // Produk baharu: dijual di semua outlet
        $this->availableOutlets = $this->shopOwner()->outlets()->pluck('id')
            ->reject(fn ($id) => in_array($id, $unavailable))->map(fn ($i) => (string) $i)->values()->all();
    }

    /** Putaran gambar (darjah ikut jam: 0/90/180/270), dikenakan pada fail bila disimpan. */
    public int $rotation = 0;

    /** Putar pratonton 90° (1 = ikut jam, -1 = lawan jam). */
    public function rotate(int $dir): void
    {
        $this->rotation = (($this->rotation + ($dir < 0 ? -90 : 90)) % 360 + 360) % 360;
    }

    /** Hanya gambar yang dimuat naik (bukan URL luar) boleh diputar. */
    public function canRotate(): bool
    {
        return (bool) $this->photo || str_starts_with((string) $this->p['image_url'], Product::UPLOAD_PREFIX);
    }

    public function updatedPhoto(): void
    {
        $this->rotation = 0;
        $this->validateOnly('photo', ['photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240']], [], ['photo' => __('gambar')]);
    }

    /**
     * Padam produk secara kekal. Rekod pesanan lama kekal (nama & harga produk disimpan pada item pesanan).
     */
    public function deleteProduct()
    {
        abort_unless($this->product, 404);
        $product = Product::where('user_id', $this->shopOwner()->id)->findOrFail($this->product->id);
        $name = $product->name;
        $image = $product->image_url;

        DB::transaction(fn () => $product->delete());
        if ($image && str_starts_with($image, Product::UPLOAD_PREFIX)) {
            Storage::disk('public')->delete('products/'.basename($image));
        }

        session()->flash('toast', __('Produk ":name" dipadam.', ['name' => $name]));

        return $this->redirectRoute('tempahan.products', navigate: true);
    }

    /** Tick produk pada landing page kedai ini yang melayan mana-mana outlet yang diberi. */
    private function addToLandingPages(Product $product, array $outletIds): void
    {
        if ($outletIds === []) {
            return;
        }
        $forms = OrderForm::where('user_id', $product->user_id)
            ->whereHas('outlets', fn ($q) => $q->whereIn('outlets.id', $outletIds))
            ->whereDoesntHave('products', fn ($q) => $q->whereKey($product->id))
            ->get();
        foreach ($forms as $form) {
            $form->products()->attach($product->id, ['sort' => (DB::table('order_form_product')->where('order_form_id', $form->id)->max('sort') ?? -1) + 1]);
        }
    }

    /** Buang gambar (pratonton baharu atau gambar sedia ada). */
    public function removePhoto(): void
    {
        $this->photo = null;
        $this->p['image_url'] = '';
        $this->rotation = 0;
    }

    /**
     * Putar fail gambar dalam storage dan simpan sebagai fail baharu (nama baharu supaya cache pelayar dikemas kini).
     * Pulangkan nama fail baharu, atau null jika gagal (gambar asal kekal).
     */
    private function rotateStoredImage(string $file, int $degrees): ?string
    {
        $disk = Storage::disk('public');
        $path = 'products/'.$file;
        if (! $disk->exists($path) || ! function_exists('imagerotate')) {
            return null;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $img = @imagecreatefromstring($disk->get($path));
        if (! $img) {
            return null;
        }
        // imagerotate memutar lawan jam; nilai negatif = ikut jam
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        $rotated = imagerotate($img, -$degrees, $transparent);
        imagedestroy($img);
        if (! $rotated) {
            return null;
        }
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        ob_start();
        match ($ext) {
            'png' => imagepng($rotated),
            'webp' => function_exists('imagewebp') ? imagewebp($rotated, null, 90) : imagepng($rotated),
            default => imagejpeg($rotated, null, 90),
        };
        $data = ob_get_clean();
        imagedestroy($rotated);

        $newExt = $ext === 'webp' && ! function_exists('imagewebp') ? 'png' : ($ext === 'jpeg' ? 'jpg' : $ext);
        $newFile = Str::random(40).'.'.$newExt;
        $disk->put('products/'.$newFile, $data);
        $disk->delete($path);

        return $newFile;
    }

    public function save()
    {
        $owner = $this->shopOwner();
        $this->validate([
            'p.name' => ['required', 'string', 'max:150'],
            'p.sku' => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->where('user_id', $owner->id)->ignore($this->product?->id)],
            'p.product_category_id' => ['nullable', Rule::in(ProductCategory::where('user_id', $owner->id)->pluck('id')->map(fn ($i) => (string) $i)->all())],
            'p.menu_id' => ['nullable', Rule::in(Menu::whereIn('outlet_id', $owner->outlets()->pluck('id'))->pluck('id')->map(fn ($i) => (string) $i)->all())],
            'p.image_url' => ['nullable', 'max:500', function ($attr, $value, $fail) {
                if (! str_starts_with($value, Product::UPLOAD_PREFIX) && ! filter_var($value, FILTER_VALIDATE_URL)) {
                    $fail(__('URL gambar tidak sah.'));
                }
            }],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'p.price' => ['nullable', 'numeric', 'min:0'],
            'p.promo_price' => ['nullable', 'numeric', 'min:0'],
            'p.stock' => ['nullable', 'integer', 'min:0'],
            'groupIds.*' => [Rule::in($this->library()->pluck('id')->map(fn ($i) => (string) $i)->all())],
            'availableOutlets.*' => [Rule::in($owner->outlets()->pluck('id')->map(fn ($i) => (string) $i)->all())],
        ], [], ['p.name' => __('nama'), 'p.price' => __('harga')]);

        $groupIds = array_values(array_unique(array_map('intval', $this->groupIds)));
        $hasVariants = $groupIds !== [];
        // Harga asas wajib (boleh 0 jika harga datang dari add-on, cth. pilihan ayam)
        if ($this->p['price'] === '' || $this->p['price'] === null) {
            return $this->addError('p.price', __('Isi harga produk. Letak 0 jika harga ikut pilihan variant.'));
        }

        // Gambar dimuat naik menggantikan URL gambar
        $oldImage = $this->product?->image_url;
        if ($this->photo) {
            $this->p['image_url'] = Product::UPLOAD_PREFIX.basename($this->photo->store('products', 'public'));
            $this->photo = null;
        }
        // Putaran: hasilkan fail baharu; fail lama (sebelum putar) dipadam
        if ($this->rotation && str_starts_with((string) $this->p['image_url'], Product::UPLOAD_PREFIX)) {
            $current = basename($this->p['image_url']);
            $isOriginal = $oldImage && basename($oldImage) === $current;
            if ($isOriginal) {
                // Salin dahulu supaya fail asal dipadam oleh logik "gambar lama" di bawah
                $copy = Str::random(40).'.'.pathinfo($current, PATHINFO_EXTENSION);
                Storage::disk('public')->copy('products/'.$current, 'products/'.$copy);
                $current = $copy;
            }
            if ($new = $this->rotateStoredImage($current, $this->rotation)) {
                $this->p['image_url'] = Product::UPLOAD_PREFIX.$new;
            } elseif ($isOriginal) {
                Storage::disk('public')->delete('products/'.$current); // salinan tidak digunakan
            }
            $this->rotation = 0;
        }

        $product = DB::transaction(function () use ($owner, $groupIds, $hasVariants) {
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
                'price_cents' => Money::toCents($this->p['price']) ?? 0,
                'promo_price_cents' => Money::toCents($this->p['promo_price']),
                'stock' => $nul($this->p['stock']),
                'max_per_order' => null, // had belian dibuang dari sistem
                'is_hidden' => (bool) $this->p['is_hidden'],
            ]);
            // Susunan diurus dengan anak panah di senarai Produk & Stok; produk baharu diletak di hujung kategori
            if (! $product->exists || $product->isDirty('product_category_id')) {
                $product->sort = (Product::where('user_id', $owner->id)->where('product_category_id', $categoryId)->whereKeyNot($product->id ?? 0)->max('sort') ?? 0) + 1;
            }
            $product->save();
            $this->logStock($product->id, null, $oldStock, $product->stock);

            // Group ditick → semua pilihan dalam group jadi variant produk (harga dari halaman Variant, tiada stok)
            $product->variantGroups()->sync($groupIds);
            $product->syncVariantLinks();

            // Outlet yang menjual produk ini sebelum disimpan (produk baharu: tiada)
            $allOutlets = $owner->outlets()->pluck('id')->map(fn ($i) => (int) $i);
            $wasAvailable = $this->product
                ? $allOutlets->diff(DB::table('outlet_product')->where('product_id', $product->id)->where('is_available', false)->pluck('outlet_id')->map(fn ($i) => (int) $i))
                : collect();

            DB::table('outlet_product')->where('product_id', $product->id)->delete();
            $nowAvailable = $allOutlets->intersect(array_map('intval', $this->availableOutlets));
            foreach ($allOutlets->diff($nowAvailable) as $oid) {
                DB::table('outlet_product')->insert(['outlet_id' => (int) $oid, 'product_id' => $product->id, 'is_available' => false]);
            }

            // Outlet yang baru ditick: tick produk ini secara automatik di landing page outlet tersebut.
            // Hanya untuk outlet baharu, supaya produk yang sengaja di-untick di landing page (cth. habis stok) tidak ditick semula.
            $this->addToLandingPages($product, $nowAvailable->diff($wasAvailable)->values()->all());

            return $product;
        });

        // Padam fail gambar lama yang telah diganti / dibuang
        if ($oldImage && $oldImage !== $product->image_url && str_starts_with($oldImage, Product::UPLOAD_PREFIX)) {
            Storage::disk('public')->delete('products/'.basename($oldImage));
        }

        if (! $this->product) {
            return $this->redirectRoute('tempahan.products.edit', $product, navigate: true);
        }
        $this->mount($product->fresh());
        $this->dispatch('toast', message: __('Produk disimpan.'));
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
            'library' => $this->library()->with('activeVariants')->get(),
        ]);
    }

    /** Variant group aktif milik kedai ini. */
    private function library()
    {
        return VariantGroup::where('user_id', $this->shopOwner()->id)->where('is_active', true)->orderBy('sort')->orderBy('id');
    }
}
