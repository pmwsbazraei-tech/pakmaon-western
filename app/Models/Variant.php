<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu pilihan dalam variant group (nama + harga). */
class Variant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_available' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(VariantGroup::class, 'variant_group_id');
    }

    /** Baris pautan produk ↔ pilihan. */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
