<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Livewire\Concerns\WithMonthFilter;
use App\Services\OutletMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FullReport extends Component
{
    use InteractsWithOutlet;
    use WithMonthFilter;

    public function render()
    {
        $outlet = $this->outlet();
        $c = OutletMetrics::for($outlet, $this->month);

        // Baris kos berubah ikut kategori (yang > 0)
        $cats = config('outlet.categories');
        $varRows = [];
        foreach ($cats as $id => $cat) {
            if (($c['varByCat'][$id] ?? 0) > 0) {
                $varRows[] = ['label' => $cat['ic'].' '.$cat['nm'], 'amount' => $c['varByCat'][$id]];
            }
        }

        return view('livewire.full-report', [
            'outlet' => $outlet,
            'c' => $c,
            'varRows' => $varRows,
        ]);
    }
}
