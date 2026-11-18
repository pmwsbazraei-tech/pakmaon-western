<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\OrderForm;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Orders extends Component
{
    use InteractsWithShop, WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $outlet = '';

    #[Url]
    public string $form = '';

    #[Url]
    public string $booking_status = '';

    #[Url]
    public string $payment_status = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'type', 'outlet', 'form', 'booking_status', 'payment_status', 'from', 'to');
        $this->resetPage();
    }

    public function filters(): array
    {
        return array_filter($this->only(['q', 'type', 'outlet', 'form', 'booking_status', 'payment_status', 'from', 'to']));
    }

    public function render()
    {
        return view('livewire.tempahan.orders', [
            'orders' => $this->visibleOrders()->filtered($this->filters())->with('outlet')->latest('id')->paginate(25),
            'outlets' => $this->visibleOutlets(),
            'forms' => OrderForm::where('user_id', $this->shopOwner()->id)->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
