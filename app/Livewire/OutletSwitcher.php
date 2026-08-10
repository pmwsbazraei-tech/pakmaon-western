<?php

namespace App\Livewire;

use Livewire\Component;

class OutletSwitcher extends Component
{
    public int $activeId = 0;

    public function mount(): void
    {
        $this->activeId = (int) auth()->user()->currentOutlet()?->id;
    }

    /** Tukar outlet aktif (dipanggil bila dropdown berubah). */
    public function updatedActiveId($value): void
    {
        $this->switchTo((int) $value);
    }

    public function switchTo(int $id)
    {
        $outlet = auth()->user()->outlets()->whereKey($id)->first();

        if ($outlet) {
            session(['active_outlet_id' => $outlet->id]);

            return $this->redirectRoute('dashboard', navigate: true);
        }
    }

    /** Cipta outlet baharu (kosong) & jadikan aktif. */
    public function createOutlet()
    {
        $outlet = auth()->user()->outlets()->create([
            'name' => 'Outlet Baru',
        ]);

        session(['active_outlet_id' => $outlet->id]);

        // Bawa ke Tetapan untuk namakan & set EPF/SOCSO dahulu
        return $this->redirectRoute('settings', navigate: true);
    }

    public function render()
    {
        return view('livewire.outlet-switcher', [
            'outlets' => auth()->user()->outlets()->orderBy('id')->get(),
        ]);
    }
}
