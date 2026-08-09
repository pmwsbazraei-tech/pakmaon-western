<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRecord extends Model
{
    protected $fillable = [
        'outlet_id', 'date', 'float_start', 'cash_sales', 'cash_payments', 'actual_count',
    ];

    protected $casts = [
        'date' => 'date',
        'float_start' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'cash_payments' => 'decimal:2',
        'actual_count' => 'decimal:2',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /** Sepatutnya = Float + Jualan Tunai − Bayaran Tunai. */
    public function expected(): float
    {
        return (float) $this->float_start + (float) $this->cash_sales - (float) $this->cash_payments;
    }

    /** Beza = Kiraan Sebenar − Sepatutnya. */
    public function diff(): float
    {
        return (float) $this->actual_count - $this->expected();
    }
}
