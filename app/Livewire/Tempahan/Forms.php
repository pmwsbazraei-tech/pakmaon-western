<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\OrderForm;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Forms extends Component
{
    use InteractsWithShop;

    public function render()
    {
        return view('livewire.tempahan.forms', [
            'forms' => OrderForm::where('user_id', $this->shopOwner()->id)->withCount('orders')->latest('id')->get(),
            'readyOutlets' => $this->visibleOutlets()->where('accepts_orders', true)->count(),
        ]);
    }
}
