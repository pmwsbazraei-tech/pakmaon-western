<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Livewire\Concerns\WithMonthFilter;
use App\Services\OutletMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SalesManager extends Component
{
    use InteractsWithOutlet;
    use WithMonthFilter;

    public string $newDate = '';

    public string $newAmount = '';

    public function mount(): void
    {
        $this->newDate = now()->format('Y-m-d');
    }

    public function add(): void
    {
        $data = $this->validate([
            'newDate' => ['required', 'date'],
            'newAmount' => ['required', 'numeric', 'min:0.01'],
        ], attributes: ['newDate' => 'tarikh', 'newAmount' => 'jualan']);

        $this->outlet()->sales()->create([
            'date' => $data['newDate'],
            'amount' => $data['newAmount'],
        ]);

        $this->reset('newAmount');
        $this->dispatch('toast', message: 'Jualan direkod');
    }

    public function delete(int $id): void
    {
        $this->outlet()->sales()->whereKey($id)->delete();
        $this->dispatch('toast', message: 'Jualan dipadam');
    }

    public function render()
    {
        $outlet = $this->outlet();
        [$year, $mon] = explode('-', $this->month);

        $days = $outlet->sales()
            ->whereYear('date', (int) $year)
            ->whereMonth('date', (int) $mon)
            ->orderByDesc('date')
            ->get();

        $c = OutletMetrics::for($outlet, $this->month);
        $avg = $days->count() ? $c['sales'] / $days->count() : 0;

        // Carta line: tarikh menaik
        $sorted = $days->sortBy('date')->values();
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $sorted->map(fn ($s) => $s->date->format('d'))->all(),
                'datasets' => [[
                    'label' => 'Jualan',
                    'data' => $sorted->map(fn ($s) => round((float) $s->amount, 2))->all(),
                    'borderColor' => '#e63946',
                    'backgroundColor' => 'rgba(230,57,70,.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => ['x' => ['ticks' => ['color' => '#4a4e56']], 'y' => ['ticks' => ['color' => '#4a4e56']]],
                'plugins' => ['legend' => ['display' => false]],
            ],
        ];

        return view('livewire.sales-manager', [
            'days' => $days,
            'sales' => $c['sales'],
            'avg' => $avg,
            'chartConfig' => $chartConfig,
        ]);
    }
}
