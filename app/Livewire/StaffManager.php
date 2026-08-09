<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Services\OutletMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StaffManager extends Component
{
    use InteractsWithOutlet;

    // Borang tambah
    public string $newName = '';

    public string $newPosition = '';

    public string $newType = 'Sepenuh Masa';

    public string $newSalary = '';

    public string $newEpfSocso = '1';

    // Buffer edit inline: [id => [name, position, type, salary, epf_socso]]
    public array $edit = [];

    public function mount(): void
    {
        $this->syncEdit();
    }

    public function syncEdit(): void
    {
        $this->edit = [];
        foreach ($this->outlet()->staff()->orderBy('id')->get() as $s) {
            $this->edit[$s->id] = [
                'name' => $s->name,
                'position' => $s->position,
                'type' => $s->type,
                'salary' => (string) $s->salary,
                'epf_socso' => $s->epf_socso ? '1' : '0',
            ];
        }
    }

    public function add(): void
    {
        $data = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newPosition' => ['nullable', 'string', 'max:255'],
            'newType' => ['required', 'in:Sepenuh Masa,Separuh Masa'],
            'newSalary' => ['required', 'numeric', 'min:0.01'],
            'newEpfSocso' => ['required', 'in:0,1'],
        ], attributes: [
            'newName' => 'nama',
            'newSalary' => 'gaji',
        ]);

        $this->outlet()->staff()->create([
            'name' => $data['newName'],
            'position' => $data['newPosition'] ?: null,
            'type' => $data['newType'],
            'salary' => $data['newSalary'],
            'epf_socso' => $data['newEpfSocso'] === '1',
        ]);

        $this->reset('newName', 'newPosition', 'newSalary');
        $this->newType = 'Sepenuh Masa';
        $this->newEpfSocso = '1';
        $this->syncEdit();
        $this->dispatch('toast', message: 'Staff ditambah');
    }

    public function updated(string $name, $value): void
    {
        if (! str_starts_with($name, 'edit.')) {
            return;
        }

        [, $id, $field] = explode('.', $name);
        $staff = $this->outlet()->staff()->find($id);
        if (! $staff) {
            return;
        }

        match ($field) {
            'salary' => $staff->salary = is_numeric($value) ? (float) $value : 0,
            'epf_socso' => $staff->epf_socso = $value === '1',
            'position' => $staff->position = $value !== '' ? $value : null,
            default => $staff->{$field} = $value,
        };
        $staff->save();
    }

    public function delete(int $id): void
    {
        $this->outlet()->staff()->whereKey($id)->delete();
        $this->syncEdit();
        $this->dispatch('toast', message: 'Staff dipadam');
    }

    public function render()
    {
        $outlet = $this->outlet();
        $staff = $outlet->staff()->orderBy('id')->get();
        $epf = (float) $outlet->epf_pct;
        $socso = (float) $outlet->socso_pct;

        $totalGaji = (float) $staff->sum(fn ($s) => (float) $s->salary);
        $totalKos = (float) $staff->sum(fn ($s) => OutletMetrics::staffTotal($s, $epf, $socso));

        return view('livewire.staff-manager', [
            'outlet' => $outlet,
            'staff' => $staff,
            'epf' => $epf,
            'socso' => $socso,
            'totalGaji' => $totalGaji,
            'totalKos' => $totalKos,
            'nFT' => $staff->where('type', 'Sepenuh Masa')->count(),
            'nPT' => $staff->where('type', 'Separuh Masa')->count(),
            'avgKos' => $staff->count() ? $totalKos / $staff->count() : 0,
        ]);
    }
}
