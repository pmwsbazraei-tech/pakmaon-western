<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    protected $fillable = [
        'user_id', 'name', 'target_sales', 'target_margin',
        'target_roi_months', 'epf_pct', 'socso_pct',
        // Tempahan online
        'slug', 'whatsapp', 'address', 'maps_url', 'accepts_orders', 'booking_enabled',
        'pickup_enabled', 'delivery_enabled', 'slot_minutes', 'slot_capacity', 'max_guests_per_booking',
        'lead_minutes', 'advance_days', 'delivery_fee_cents',
    ];

    protected $casts = [
        'target_sales' => 'decimal:2',
        'target_margin' => 'decimal:2',
        'target_roi_months' => 'integer',
        'epf_pct' => 'decimal:2',
        'socso_pct' => 'decimal:2',
        'accepts_orders' => 'boolean',
        'booking_enabled' => 'boolean',
        'pickup_enabled' => 'boolean',
        'delivery_enabled' => 'boolean',
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

    // ---- Tempahan online ----

    public function hours(): HasMany
    {
        return $this->hasMany(OutletHour::class)->orderBy('weekday');
    }

    public function closures(): HasMany
    {
        return $this->hasMany(OutletClosure::class)->orderBy('date');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Akaun staf cawangan yang diberi akses tempahan outlet ini. */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }

    /** @return array<int> */
    public function unavailableProductIds(): array
    {
        return \DB::table('outlet_product')->where('outlet_id', $this->id)
            ->where('is_available', false)->pluck('product_id')->all();
    }
}
