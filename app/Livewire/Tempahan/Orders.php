<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\OrderForm;
use App\Services\OrderService;
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

    /** @var array<int, string> ID pesanan yang ditanda untuk dipadam */
    public array $selected = [];

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'selected')) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function mount(): void
    {
        if ($msg = session('toast')) {
            $this->dispatch('toast', message: $msg);
        }
    }

    public function deleteSelected(OrderService $service): void
    {
        abort_unless(auth()->user()->canManageShop(), 403);

        $ids = array_map('intval', $this->selected);
        $deleted = 0;
        $kept = 0;
        foreach ($this->visibleOrders()->whereIn('id', $ids)->get() as $order) {
            $service->delete($order, auth()->id()) ? $deleted++ : $kept++;
        }
        $this->selected = [];

        $msg = $deleted.' pesanan dipadam.';
        if ($kept) {
            $msg .= ' '.$kept.' tidak dipadam kerana sudah dibayar online (batalkan & refund sahaja).';
        }
        $this->dispatch('toast', message: $msg);
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
            'canDelete' => auth()->user()->canManageShop(),
            'forms' => OrderForm::where('user_id', $this->shopOwner()->id)->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
