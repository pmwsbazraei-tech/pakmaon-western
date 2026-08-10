<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    protected $fillable = [
        'user_id', 'name', 'target_sales', 'target_margin',
        'target_roi_months', 'epf_pct', 'socso_pct',
    ];

    protected $casts = [
        'target_sales' => 'decimal:2',
        'target_margin' => 'decimal:2',
        'target_roi_months' => 'integer',
        'epf_pct' => 'decimal:2',
        'socso_pct' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function capexItems(): HasMany
    {
        return $this->hasMany(CapexItem::class);
    }

    public function fixedCosts(): HasMany
    {
        return $this->hasMany(FixedCost::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function cashRecords(): HasMany
    {
        return $this->hasMany(CashRecord::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(OutletCategory::class);
    }
}
