<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\OrderForm;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Overview extends Component
{
    use InteractsWithShop;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $outlet = '';

    #[Url]
    public string $form = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function render()
    {
        $filters = ['from' => $this->from, 'to' => $this->to, 'outlet' => $this->outlet, 'form' => $this->form];
        $base = fn () => $this->visibleOrders()->filtered($filters);
        $scope = fn () => $this->visibleOrders()->filtered(['outlet' => $this->outlet, 'form' => $this->form]);

        return view('livewire.tempahan.overview', [
            'stats' => [
                'bookings' => $base()->where('type', 'booking')->count(),
                'purchases' => $base()->where('type', 'purchase')->count(),
                'paid' => $base()->where('payment_status', 'paid')->count(),
                // Jualan hanya dari bayaran yang disahkan gateway
                'sales' => (int) $base()->where('payment_status', 'paid')->sum('total_cents'),
            ],
            'needsAction' => $scope()->with('outlet')
                ->where(fn ($q) => $q->where('booking_status', 'pending')
                    ->orWhere(fn ($w) => $w->where('type', 'purchase')->where('payment_status', 'paid')->where('fulfillment_status', 'new')))
                ->where(fn ($q) => $q->whereNull('booking_at')->orWhere('booking_at', '>=', now()->subHours(2)))
                ->orderByRaw('booking_at is null, booking_at')->limit(15)->get(),
            'upcoming' => $scope()->with('outlet')->where('type', 'booking')
                ->whereIn('booking_status', ['pending', 'confirmed'])
                ->whereBetween('booking_at', [now(), now()->addDays(7)])
                ->orderBy('booking_at')->limit(15)->get(),
            'outlets' => $this->visibleOutlets(),
            'forms' => OrderForm::where('user_id', $this->shopOwner()->id)->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
