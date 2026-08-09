<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ['outlet_id', 'name', 'price'];

    protected $casts = ['price' => 'decimal:2'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(MenuIngredient::class);
    }

    /** Jumlah kos bahan setiap hidangan. */
    public function cost(): float
    {
        return (float) $this->ingredients->sum(fn ($i) => (float) $i->cost);
    }
}
