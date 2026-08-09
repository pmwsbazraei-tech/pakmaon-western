<?php

namespace App\Livewire\Concerns;

use App\Models\Outlet;

trait InteractsWithOutlet
{
    protected ?Outlet $outletCache = null;

    /** Outlet aktif milik user log masuk. */
    public function outlet(): Outlet
    {
        return $this->outletCache ??= auth()->user()->currentOutlet();
    }
}
