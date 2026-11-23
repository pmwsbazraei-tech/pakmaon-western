<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Variant group (cth. "Chicken") dengan beberapa pilihan; ditick pada produk. */
class VariantGroup extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class)->orderBy('sort')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Kemas kini variant semua produk yang menanda group ini. */
    public function syncProducts(): void
    {
        foreach ($this->products()->get() as $product) {
            $product->syncVariantLinks();
        }
    }
}
