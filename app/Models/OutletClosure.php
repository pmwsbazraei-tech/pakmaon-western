<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletClosure extends Model
{
    public $timestamps = false;

    protected $fillable = ['outlet_id', 'date', 'reason'];

    protected $casts = ['date' => 'date'];
}
