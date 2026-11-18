<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletHour extends Model
{
    public $timestamps = false;

    protected $fillable = ['outlet_id', 'weekday', 'opens_at', 'closes_at', 'is_closed'];

    public const DAYS = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];

    protected $casts = ['is_closed' => 'boolean'];
}
