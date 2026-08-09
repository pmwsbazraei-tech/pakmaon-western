<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

trait WithMonthFilter
{
    /** Bulan laporan (YYYY-MM). Terikat pada input type="month" via wire:model.live. */
    #[Url]
    public string $month = '';

    public function mountWithMonthFilter(): void
    {
        if ($this->month === '') {
            $this->month = now()->format('Y-m');
        }
    }
}
