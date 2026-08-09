<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'outlet_id', 'name', 'position', 'salary', 'type', 'epf_socso',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'epf_socso' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
