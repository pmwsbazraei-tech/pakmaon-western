<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedCost extends Model
{
    protected $fillable = ['outlet_id', 'name', 'group', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
