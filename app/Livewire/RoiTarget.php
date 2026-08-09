<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Livewire\Concerns\WithMonthFilter;
use App\Services\OutletMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RoiTarget extends Component
{
    use InteractsWithOutlet;
    use WithMonthFilter;

    public string $targetSales = '';

    public string $targetMargin = '';

    public string $targetRoiMonths = '';

    public function mount(): void
    {
        $outlet = $this->outlet();
        $this->targetSales = (string) (0 + $outlet->target_sales);
        $this->targetMargin = (string) (0 + $outlet->target_margin);
        $this->targetRoiMonths = (string) $outlet->target_roi_months;
    }

    public function saveTargets(): void
    {
        $data = $this->validate([
            'targetSales' => ['required', 'numeric', 'min:0'],
            'targetMargin' => ['required', 'numeric', 'min:0'],
            'targetRoiMonths' => ['required', 'integer', 'min:0'],
        ], attributes: [
            'targetSales' => 'target jualan',
            'targetMargin' => 'target margin',
            'targetRoiMonths' => 'target balik modal',
        ]);

        $this->outlet()->update([
            'target_sales' => $data['targetSales'],
            'target_margin' => $data['targetMargin'],
            'target_roi_months' => $data['targetRoiMonths'],
        ]);

        $this->dispatch('toast', message: 'Sasaran disimpan');
    }

    public function render()
    {
        $outlet = $this->outlet();
        $c = OutletMetrics::for($outlet, $this->month);

        // Carta: unjuran balik modal kumulatif 24 bulan
        $labels = range(1, 24);
        $cumTarget = array_map(fn ($mo) => round(min($c['capex'], $c['targetNet'] * $mo), 2), $labels);
        $cumActual = array_map(fn ($mo) => round(min($c['capex'], max(0, $c['net']) * $mo), 2), $labels);
        $modalLine = array_map(fn () => round($c['capex'], 2), $labels);

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Sasaran', 'data' => $cumTarget, 'borderColor' => '#2ecc71', 'fill' => false, 'tension' => 0.2],
                    ['label' => 'Semasa', 'data' => $cumActual, 'borderColor' => '#4aa3ff', 'fill' => false, 'tension' => 0.2],
                    ['label' => 'Modal', 'data' => $modalLine, 'borderColor' => '#e63946', 'borderDash' => [6, 6], 'fill' => false, 'pointRadius' => 0],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => ['title' => ['display' => true, 'text' => 'Bulan', 'color' => '#4a4e56'], 'ticks' => ['color' => '#4a4e56']],
                    'y' => ['ticks' => ['color' => '#4a4e56']],
                ],
                'plugins' => ['legend' => ['labels' => ['color' => '#4a4e56']]],
            ],
        ];

        return view('livewire.roi-target', [
            'c' => $c,
            'chartConfig' => $chartConfig,
        ]);
    }
}
