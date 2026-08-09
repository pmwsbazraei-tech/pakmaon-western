<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Livewire\Concerns\WithMonthFilter;
use App\Services\OutletMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PettyCash extends Component
{
    use InteractsWithOutlet;
    use WithMonthFilter;

    public string $newDate = '';

    public string $newFloat = '100';

    public string $newCashSales = '';

    public string $newCashPayments = '0';

    public string $newActual = '';

    public function mount(): void
    {
        $this->newDate = now()->format('Y-m-d');
    }

    public function add(): void
    {
        $data = $this->validate([
            'newDate' => ['required', 'date'],
            'newFloat' => ['nullable', 'numeric', 'min:0'],
            'newCashSales' => ['nullable', 'numeric', 'min:0'],
            'newCashPayments' => ['nullable', 'numeric', 'min:0'],
            'newActual' => ['nullable', 'numeric', 'min:0'],
        ], attributes: ['newDate' => 'tarikh']);

        $this->outlet()->cashRecords()->create([
            'date' => $data['newDate'],
            'float_start' => (float) ($data['newFloat'] ?: 0),
            'cash_sales' => (float) ($data['newCashSales'] ?: 0),
            'cash_payments' => (float) ($data['newCashPayments'] ?: 0),
            'actual_count' => (float) ($data['newActual'] ?: 0),
        ]);

        $this->reset('newCashSales', 'newActual');
        $this->newFloat = '100';
        $this->newCashPayments = '0';
        $this->dispatch('toast', message: 'Rekod tutup harian disimpan');
    }

    public function delete(int $id): void
    {
        $this->outlet()->cashRecords()->whereKey($id)->delete();
        $this->dispatch('toast', message: 'Rekod dipadam');
    }

    public function render()
    {
        [$year, $mon] = explode('-', $this->month);

        $rows = $this->outlet()->cashRecords()
            ->whereYear('date', (int) $year)
            ->whereMonth('date', (int) $mon)
            ->orderByDesc('date')
            ->get();

        $totIn = (float) $rows->sum(fn ($r) => (float) $r->cash_sales);
        $totDiff = (float) $rows->sum(fn ($r) => OutletMetrics::cashDiff($r));

        return view('livewire.petty-cash', [
            'rows' => $rows,
            'totIn' => $totIn,
            'totDiff' => $totDiff,
        ]);
    }
}
