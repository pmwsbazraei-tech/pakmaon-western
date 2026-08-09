<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Models\Equipment as EquipmentModel;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Equipment extends Component
{
    use InteractsWithOutlet;

    public string $newName = '';

    public string $newGroup = 'Dapur';

    public string $newQty = '1';

    public string $newPrice = '';

    public function add(): void
    {
        $groups = config('outlet.equipment_groups');
        $data = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newGroup' => ['required', 'in:'.implode(',', $groups)],
            'newQty' => ['required', 'integer', 'min:1'],
            'newPrice' => ['required', 'numeric', 'min:0.01'],
        ], attributes: ['newName' => 'peralatan', 'newPrice' => 'harga']);

        $this->outlet()->equipment()->create([
            'name' => $data['newName'],
            'group' => $data['newGroup'],
            'qty' => (int) $data['newQty'],
            'unit_price' => $data['newPrice'],
            'status' => 'perlu',
        ]);

        $this->reset('newName', 'newPrice');
        $this->newGroup = 'Dapur';
        $this->newQty = '1';
        $this->dispatch('toast', message: 'Peralatan ditambah');
    }

    public function toggle(int $id): void
    {
        $item = $this->outlet()->equipment()->find($id);
        if ($item) {
            $item->status = $item->status === 'beli' ? 'perlu' : 'beli';
            $item->save();
        }
    }

    public function delete(int $id): void
    {
        $this->outlet()->equipment()->whereKey($id)->delete();
        $this->dispatch('toast', message: 'Peralatan dipadam');
    }

    public function render()
    {
        $items = $this->outlet()->equipment()->orderBy('id')->get();
        $tot = (float) $items->sum(fn (EquipmentModel $e) => $e->subtotal());
        $beli = (float) $items->where('status', 'beli')->sum(fn (EquipmentModel $e) => $e->subtotal());
        $perlu = $tot - $beli;
        $pct = $tot > 0 ? $beli / $tot * 100 : 0;

        return view('livewire.equipment', [
            'grouped' => $items->groupBy('group'),
            'tot' => $tot,
            'beli' => $beli,
            'perlu' => $perlu,
            'pct' => $pct,
        ]);
    }
}
