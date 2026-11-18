<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Produk jualan online (katalog landing page), milik pemilik dan dikongsi semua outletnya. */
class Product extends Model
{
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
