<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletCategory extends Model
{
    protected $fillable = ['outlet_id', 'key', 'name', 'icon', 'is_cogs'];

    protected $casts = ['is_cogs' => 'boolean'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
