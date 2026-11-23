<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Outlet;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Tetapan tempahan bagi setiap outlet: WhatsApp, lokasi, waktu operasi, cuti, slot, pickup/delivery. */
#[Layout('layouts.app')]
class OutletSettings extends Component
{
    use InteractsWithShop;

    #[Url]
    public ?int $outlet = null;

    public array $o = [];

    public array $hours = [];

    public string $closures = '';

    public function mount(): void
    {
        $this->outlet ??= $this->shopOwner()->outlets()->orderBy('id')->value('id');
        $this->load();
    }

    public function updatedOutlet(): void
    {
        $this->resetErrorBag();
        $this->load();
    }

    private function model(): Outlet
    {
        return $this->shopOwner()->outlets()->findOrFail($this->outlet);
    }

    private function load(): void
    {
        $m = $this->model()->load('hours', 'closures');
        $this->o = [
            'whatsapp' => $m->whatsapp ? Phone::display($m->whatsapp) : '', 'address' => $m->address ?? '', 'maps_url' => $m->maps_url ?? '',
            'accepts_orders' => (bool) $m->accepts_orders, 'booking_enabled' => (bool) $m->booking_enabled,
            'pickup_enabled' => (bool) $m->pickup_enabled, 'delivery_enabled' => (bool) $m->delivery_enabled,
            'slot_minutes' => (string) $m->slot_minutes, 'slot_capacity' => (string) $m->slot_capacity,
            'max_guests_per_booking' => (string) $m->max_guests_per_booking, 'lead_minutes' => (string) $m->lead_minutes,
            'advance_days' => (string) $m->advance_days, 'delivery_fee' => number_format($m->delivery_fee_cents / 100, 2, '.', ''),
        ];
        $byDay = $m->hours->keyBy('weekday');
        $this->hours = collect(range(0, 6))->map(fn ($d) => [
            'opens_at' => isset($byDay[$d]) && $byDay[$d]->opens_at ? substr($byDay[$d]->opens_at, 0, 5) : '',
            'closes_at' => isset($byDay[$d]) && $byDay[$d]->closes_at ? substr($byDay[$d]->closes_at, 0, 5) : '',
            'is_closed' => isset($byDay[$d]) ? (bool) $byDay[$d]->is_closed : false,
        ])->all();
        $this->closures = $m->closures->map(fn ($c) => $c->date->toDateString().($c->reason ? ' '.$c->reason : ''))->implode("\n");
    }

    public function save(): void
    {
        $outlet = $this->model();
        $this->validate([
            'o.whatsapp' => ['nullable', 'string'],
            'o.address' => ['nullable', 'string', 'max:300'],
            'o.maps_url' => ['nullable', 'url', 'max:500'],
            'o.slot_minutes' => ['required', Rule::in(['15', '30', '45', '60', '90', '120'])],
            'o.slot_capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'o.max_guests_per_booking' => ['required', 'integer', 'min:1', 'max:500'],
            'o.lead_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'o.advance_days' => ['required', 'integer', 'min:0', 'max:365'],
            'o.delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ]);

        $wa = Phone::normalize($this->o['whatsapp']);
        if ($this->o['whatsapp'] !== '' && ! $wa) {
            $this->addError('o.whatsapp', __('Nombor WhatsApp tidak sah.'));

            return;
        }
        if ($this->o['accepts_orders'] && ! $wa) {
            $this->addError('o.whatsapp', __('Isi nombor WhatsApp sebelum outlet menerima tempahan.'));

            return;
        }

        $outlet->update([
            'slug' => $outlet->slug ?: Str::slug($outlet->name).'-'.$outlet->id,
            'whatsapp' => $wa,
            'address' => $this->o['address'] ?: null,
            'maps_url' => $this->o['maps_url'] ?: null,
            'accepts_orders' => (bool) $this->o['accepts_orders'],
            'booking_enabled' => (bool) $this->o['booking_enabled'],
            'pickup_enabled' => (bool) $this->o['pickup_enabled'],
            'delivery_enabled' => (bool) $this->o['delivery_enabled'],
            'slot_minutes' => (int) $this->o['slot_minutes'],
            'slot_capacity' => (int) $this->o['slot_capacity'],
            'max_guests_per_booking' => (int) $this->o['max_guests_per_booking'],
            'lead_minutes' => (int) $this->o['lead_minutes'],
            'advance_days' => (int) $this->o['advance_days'],
            'delivery_fee_cents' => Money::toCents($this->o['delivery_fee']) ?? 0,
        ]);

        foreach ($this->hours as $d => $h) {
            $closed = $h['is_closed'] || $h['opens_at'] === '' || $h['closes_at'] === '';
            $outlet->hours()->updateOrCreate(['weekday' => $d], [
                'opens_at' => $h['opens_at'] ?: null, 'closes_at' => $h['closes_at'] ?: null, 'is_closed' => $closed,
            ]);
        }

        // Format setiap baris: 2026-12-25 Hari Krismas
        $outlet->closures()->delete();
        foreach (preg_split('/\R/', $this->closures) as $line) {
            if (preg_match('/^\s*(\d{4}-\d{2}-\d{2})\s*(.*)$/', $line, $m)) {
                $outlet->closures()->firstOrCreate(['date' => $m[1]], ['reason' => trim($m[2]) ?: null]);
            }
        }

        $this->load();
        $this->dispatch('toast', message: __('Tetapan outlet disimpan.'));
    }

    public function render()
    {
        return view('livewire.tempahan.outlet-settings', [
            'outlets' => $this->shopOwner()->outlets()->orderBy('id')->get(['id', 'name', 'accepts_orders']),
        ]);
    }
}
