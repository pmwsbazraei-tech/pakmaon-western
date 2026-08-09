<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CapexBudget extends Component
{
    use InteractsWithOutlet;

    public string $newName = '';

    public string $newAmount = '';

    public array $edit = [];

    public function mount(): void
    {
        $this->syncEdit();
    }

    public function syncEdit(): void
    {
        $this->edit = [];
        foreach ($this->outlet()->capexItems()->orderBy('id')->get() as $c) {
            $this->edit[$c->id] = ['amount' => (string) $c->amount];
        }
    }

    public function add(): void
    {
        $data = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newAmount' => ['required', 'numeric', 'min:0.01'],
        ], attributes: ['newName' => 'perkara', 'newAmount' => 'kos']);

        $this->outlet()->capexItems()->create([
            'name' => $data['newName'],
            'amount' => $data['newAmount'],
        ]);

        $this->reset('newName', 'newAmount');
        $this->syncEdit();
        $this->dispatch('toast', message: 'Item modal ditambah');
    }

    public function updated(string $name, $value): void
    {
        if (! str_starts_with($name, 'edit.')) {
            return;
        }

        [, $id] = explode('.', $name);
        $item = $this->outlet()->capexItems()->find($id);
        if ($item) {
            $item->amount = is_numeric($value) ? (float) $value : 0;
            $item->save();
        }
    }

    public function delete(int $id): void
    {
        $this->outlet()->capexItems()->whereKey($id)->delete();
        $this->syncEdit();
        $this->dispatch('toast', message: 'Item modal dipadam');
    }

    public function render()
    {
        $items = $this->outlet()->capexItems()->orderBy('id')->get();

        return view('livewire.capex-budget', [
            'items' => $items,
            'total' => (float) $items->sum(fn ($c) => (float) $c->amount),
        ]);
    }
}
