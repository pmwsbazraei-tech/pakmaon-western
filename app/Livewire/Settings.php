<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Support\DefaultOutletData;
use App\Support\OutletJson;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Settings extends Component
{
    use InteractsWithOutlet;
    use WithFileUploads;

    public string $name = '';

    public string $epf = '';

    public string $socso = '';

    public $importFile;

    public function mount(): void
    {
        $outlet = $this->outlet();
        $this->name = $outlet->name;
        $this->epf = (string) (0 + $outlet->epf_pct);
        $this->socso = (string) (0 + $outlet->socso_pct);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'epf' => ['required', 'numeric', 'min:0'],
            'socso' => ['required', 'numeric', 'min:0'],
        ], attributes: ['name' => 'nama outlet']);

        $this->outlet()->update([
            'name' => $data['name'],
            'epf_pct' => $data['epf'],
            'socso_pct' => $data['socso'],
        ]);

        $this->dispatch('toast', message: 'Disimpan');
    }

    /** Padam outlet ini sepenuhnya (tak boleh jika ini satu-satunya outlet). */
    public function deleteOutlet()
    {
        $user = auth()->user();

        if ($user->outlets()->count() <= 1) {
            $this->dispatch('toast', message: 'Tak boleh padam — ini satu-satunya outlet anda');

            return;
        }

        session()->forget('active_outlet_id');
        $this->outlet()->delete();   // cascade padam semua data outlet

        return $this->redirectRoute('dashboard', navigate: true);
    }

    public function export()
    {
        $json = json_encode(OutletJson::export($this->outlet()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'pakmaon-data-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(fn () => print ($json), $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(): void
    {
        $this->validate(['importFile' => ['required', 'file', 'mimes:json,txt', 'max:5120']]);

        $content = file_get_contents($this->importFile->getRealPath());
        $data = json_decode($content, true);

        if (! is_array($data)) {
            $this->dispatch('toast', message: 'Fail tidak sah');

            return;
        }

        DB::transaction(fn () => OutletJson::import($this->outlet(), $data));

        $this->reset('importFile');
        $this->mount();
        $this->dispatch('toast', message: 'Import berjaya');
    }

    public function resetData(): void
    {
        $outlet = $this->outlet();

        DB::transaction(function () use ($outlet) {
            OutletJson::wipe($outlet);
            // Isi semula data DEFAULT (macam prototaip reset ke objek DEFAULT)
            $outlet->update(DefaultOutletData::SETTINGS);
            $outlet->staff()->createMany(DefaultOutletData::STAFF);
            $outlet->equipment()->createMany(DefaultOutletData::EQUIPMENT);
            $outlet->capexItems()->createMany(DefaultOutletData::CAPEX);
            $outlet->fixedCosts()->createMany(DefaultOutletData::FIXED);
            foreach (DefaultOutletData::MENU as $menu) {
                $created = $outlet->menus()->create(['name' => $menu['name'], 'price' => $menu['price']]);
                $created->ingredients()->createMany($menu['ing']);
            }
        });

        $this->mount();
        $this->dispatch('toast', message: 'Data direset');
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
