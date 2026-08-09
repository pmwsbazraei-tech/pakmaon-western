<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Services\OutletMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FixedCosts extends Component
{
    use InteractsWithOutlet;

    public string $newName = '';

    public string $newGroup = 'Gaji';

    public string $newAmount = '';

    public array $edit = [];

    public function mount(): void
    {
        $this->syncEdit();
    }

    public function syncEdit(): void
    {
        $this->edit = [];
        foreach ($this->outlet()->fixedCosts()->orderBy('id')->get() as $f) {
            $this->edit[$f->id] = [
                'name' => $f->name,
                'group' => $f->group,
                'amount' => (string) $f->amount,
            ];
        }
    }

    public function add(): void
    {
        $groups = config('outlet.fixed_cost_groups');
        $data = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newGroup' => ['required', 'in:'.implode(',', $groups)],
            'newAmount' => ['required', 'numeric', 'min:0.01'],
        ], attributes: ['newName' => 'perkara', 'newAmount' => 'amaun']);

        $this->outlet()->fixedCosts()->create([
            'name' => $data['newName'],
            'group' => $data['newGroup'],
            'amount' => $data['newAmount'],
        ]);

        $this->reset('newName', 'newAmount');
        $this->newGroup = 'Gaji';
        $this->syncEdit();
        $this->dispatch('toast', message: 'Kos tetap ditambah');
    }

    public function updated(string $name, $value): void
    {
        if (! str_starts_with($name, 'edit.')) {
            return;
        }

        [, $id, $field] = explode('.', $name);
        $item = $this->outlet()->fixedCosts()->find($id);
        if (! $item) {
            return;
        }

        if ($field === 'amount') {
            $item->amount = is_numeric($value) ? (float) $value : 0;
        } else {
            $item->{$field} = $value;
        }
        $item->save();
    }

    public function delete(int $id): void
    {
        $this->outlet()->fixedCosts()->whereKey($id)->delete();
        $this->syncEdit();
        $this->dispatch('toast', message: 'Kos tetap dipadam');
    }

    public function render()
    {
        $outlet = $this->outlet();
        $items = $outlet->fixedCosts()->orderBy('id')->get();
        $total = (float) $items->sum(fn ($f) => (float) $f->amount);
        $staffCost = OutletMetrics::make($outlet, now()->format('Y-m'))->staffCost();

        return view('livewire.fixed-costs', [
            'items' => $items,
            'total' => $total,
            'staffCost' => $staffCost,
            'fixedTotal' => $total + $staffCost,
            'editGroups' => config('outlet.fixed_cost_groups_selectable'),
            'addGroups' => config('outlet.fixed_cost_groups'),
        ]);
    }
}
