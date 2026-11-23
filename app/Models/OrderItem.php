<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    /** options: senarai add-on dipilih [{id, group, name, price_cents}] */
    protected function casts(): array
    {
        return ['options' => 'array'];
    }
}
