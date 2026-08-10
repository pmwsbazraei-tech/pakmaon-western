<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Livewire\Concerns\WithMonthFilter;
use App\Services\OutletMetrics;
use App\Support\Categories;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use InteractsWithOutlet;
    use WithMonthFilter;

    public function render()
    {
        $outlet = $this->outlet();
        $c = OutletMetrics::for($outlet, $this->month);
        $cats = Categories::all($outlet);

        // ---- Carta doughnut: pecahan kos ikut kategori + kos tetap ----
        $colors = ['#e63946', '#f4a261', '#4aa3ff', '#2ecc71', '#f4c430', '#9b5de5', '#00bbf9', '#8d99ae'];
        $pieLabels = [];
        $pieData = [];
        foreach ($cats as $id => $cat) {
            if (($c['varByCat'][$id] ?? 0) > 0) {
                $pieLabels[] = $cat['nm'];
                $pieData[] = round($c['varByCat'][$id], 2);
            }
        }
        $pieLabels[] = 'Kos Tetap (gaji/sewa)';
        $pieData[] = round($c['fixedTotal'], 2);

        $pieConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $pieLabels,
                'datasets' => [['data' => $pieData, 'backgroundColor' => $colors]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'right', 'labels' => ['color' => '#4a4e56', 'font' => ['size' => 11]]]],
            ],
        ];

        // ---- Carta bar 6 bulan terakhir ----
        $months = [];
        $base = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        for ($i = 5; $i >= 0; $i--) {
            $months[] = $base->copy()->subMonths($i)->format('Y-m');
        }
        $series = array_map(fn ($m) => OutletMetrics::for($outlet, $m), $months);
        $barLabels = array_map(fn ($m) => substr($m, 5).'/'.substr($m, 2, 2), $months);

        $barConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $barLabels,
                'datasets' => [
                    ['label' => 'Jualan', 'data' => array_map(fn ($x) => round($x['sales'], 2), $series), 'backgroundColor' => '#4aa3ff'],
                    ['label' => 'Kos', 'data' => array_map(fn ($x) => round($x['expenses'], 2), $series), 'backgroundColor' => '#e63946'],
                    ['label' => 'Untung', 'data' => array_map(fn ($x) => round($x['net'], 2), $series), 'backgroundColor' => '#2ecc71'],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => ['x' => ['ticks' => ['color' => '#4a4e56']], 'y' => ['ticks' => ['color' => '#4a4e56']]],
                'plugins' => ['legend' => ['labels' => ['color' => '#4a4e56']]],
            ],
        ];

        return view('livewire.dashboard', [
            'c' => $c,
            'pieConfig' => $pieConfig,
            'barConfig' => $barConfig,
        ]);
    }
}
