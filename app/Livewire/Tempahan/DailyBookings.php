<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Paparan harian ikut outlet untuk staf kedai dan dapur. */
#[Layout('layouts.app')]
class DailyBookings extends Component
{
    use InteractsWithShop;

    #[Url]
    public string $date = '';

    #[Url]
    public ?int $outlet = null;

    #[Url]
    public bool $showCancelled = false;

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
        $outlets = $this->visibleOutlets();
        $this->outlet ??= ($outlets->firstWhere('accepts_orders', true) ?? $outlets->first())?->id;
    }

    public function shiftDay(int $days): void
    {
        $this->date = CarbonImmutable::parse($this->date)->addDays($days)->toDateString();
    }

    public function render()
    {
        abort_if($this->outlet && ! auth()->user()->canAccessOutlet($this->outlet), 403);
        $day = CarbonImmutable::parse($this->date, config('app.timezone'));

        $bookings = $this->visibleOrders()->with('items')
            ->where('type', 'booking')->where('outlet_id', $this->outlet)
            ->whereBetween('booking_at', [$day->startOfDay(), $day->endOfDay()])
            ->when(! $this->showCancelled, fn ($q) => $q->where('booking_status', '!=', 'cancelled'))
            ->orderBy('booking_at')->get();
        $active = $bookings->where('booking_status', '!=', 'cancelled');

        return view('livewire.tempahan.daily-bookings', [
            'day' => $day,
            'outlets' => $this->visibleOutlets(),
            'slots' => $bookings->groupBy(fn ($o) => $o->booking_at->format('H:i')),
            'menuTotals' => $active->flatMap->items
                ->groupBy(fn ($i) => $i->product_name.($i->variant_name ? ' ('.$i->variant_name.')' : ''))
                ->map->sum('quantity')->sortKeys(),
            'guestTotal' => $active->sum('guests'),
            'count' => $bookings->count(),
        ]);
    }
}
