<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Variant dalam senarai dikongsi (nama + harga), boleh ditick pada banyak produk. */
class Variant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Baris pautan produk ↔ variant. */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** Salin nama/harga terkini ke semua produk yang menggunakan variant ini. */
    public function syncToProducts(): void
    {
        $this->productVariants()->update([
            'name' => $this->name, 'sku' => $this->sku, 'price_cents' => $this->price_cents,
            'promo_price_cents' => $this->promo_price_cents, 'sort' => $this->sort,
        ]);
    }
}
