<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Variant asal dalam senarai Variant (null untuk rekod lama). */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function effectivePrice(): int
    {
        return $this->promo_price_cents ?? $this->price_cents;
    }
}
