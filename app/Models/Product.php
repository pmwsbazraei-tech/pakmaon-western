<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Produk jualan online (katalog landing page), milik pemilik dan dikongsi semua outletnya. */
class Product extends Model
{
    /** Gambar yang dimuat naik disimpan sebagai /media/products/{fail}, dihidang oleh laluan product.image. */
    public const UPLOAD_PREFIX = '/media/products/';

    protected $guarded = ['id'];

    protected $casts = ['has_variants' => 'boolean', 'is_hidden' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /** Pautan pilihan ke menu food cost. */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    /** Variant group yang ditick pada produk ini. */
    public function variantGroups(): BelongsToMany
    {
        return $this->belongsToMany(VariantGroup::class)->orderBy('sort')->orderBy('variant_groups.id');
    }

    /**
     * Selaraskan pautan product_variants dengan semua pilihan dalam group yang ditick.
     * Nama/harga disalin dari pilihan; pautan yang tidak lagi berkenaan dinyahaktifkan (sejarah pesanan kekal).
     */
    public function syncVariantLinks(): void
    {
        $groups = $this->variantGroups()->where('is_active', true)->with('activeVariants')->get();
        $keep = [];
        foreach ($groups->values() as $gi => $g) {
            foreach ($g->activeVariants->values() as $vi => $v) {
                $link = $this->variants()->firstOrNew(['variant_id' => $v->id]);
                $link->fill([
                    'name' => $v->name, 'sku' => $v->sku, 'price_cents' => $v->price_cents, 'promo_price_cents' => $v->promo_price_cents,
                    'stock' => null, 'max_per_order' => null, 'is_active' => true, 'sort' => $gi * 1000 + $vi,
                ])->save();
                $keep[] = $link->id;
            }
        }
        $this->variants()->whereNotIn('id', $keep)->update(['is_active' => false]);

        $hasVariants = $groups->isNotEmpty();
        if ($this->has_variants !== $hasVariants) {
            $this->has_variants = $hasVariants;
            // Tiada group lagi dan tiada harga produk: sembunyikan supaya pelanggan tidak nampak produk tanpa harga
            if (! $hasVariants && $this->price_cents === null) {
                $this->is_hidden = true;
            }
            $this->save();
        }
    }

    public function effectivePrice(): ?int
    {
        return $this->promo_price_cents ?? $this->price_cents;
    }

    public function isSoldOut(): bool
    {
        if ($this->has_variants) {
            return $this->activeVariants->every(fn ($v) => $v->stock !== null && $v->stock <= 0);
        }

        return $this->stock !== null && $this->stock <= 0;
    }
}
