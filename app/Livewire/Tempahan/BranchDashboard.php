<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Livewire\Concerns\WithMonthFilter;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dashboard utama: bilangan pesanan tempahan online ikut cawangan dan status
 * (Baharu / Deposit / Paid / Dibatalkan) bagi bulan dipilih. Staf hanya nampak cawangan sendiri.
 */
#[Layout('layouts.app')]
class BranchDashboard extends Component
{
    use InteractsWithShop, WithMonthFilter;

    public const BUCKETS = ['new' => 'Baharu', 'deposit' => 'Deposit', 'paid' => 'Paid', 'cancelled' => 'Dibatalkan'];

    /** Kategori satu pesanan untuk dashboard. Batal mengatasi status bayaran. */
    public static function bucket(object $o): string
    {
        return match (true) {
            $o->booking_status === 'cancelled' || $o->fulfillment_status === 'cancelled' => 'cancelled',
            $o->payment_status === 'paid' => 'paid',
            $o->payment_status === 'partial' => 'deposit',
            default => 'new',
        };
    }

    public function render()
    {
        try {
            $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        } catch (\Throwable) {
            $start = now()->startOfMonth();
            $this->month = $start->format('Y-m');
        }

        $orders = $this->visibleOrders()
            ->whereBetween('created_at', [$start, $start->copy()->endOfMonth()])
            ->get(['id', 'outlet_id', 'booking_status', 'payment_status', 'fulfillment_status', 'total_cents', 'deposit_cents']);

        $empty = fn () => array_fill_keys(array_keys(self::BUCKETS), 0) + ['total' => 0, 'sales_cents' => 0, 'deposit_cents' => 0];
        $byOutlet = [];
        foreach ($orders as $o) {
            $row = $byOutlet[$o->outlet_id] ?? $empty();
            $b = self::bucket($o);
            $row[$b]++;
            $row['total']++;
            if ($b === 'paid') {
                $row['sales_cents'] += $o->total_cents;
            } elseif ($b === 'deposit') {
                $row['deposit_cents'] += $o->deposit_cents;
            }
            $byOutlet[$o->outlet_id] = $row;
        }

        // Cawangan yang terima tempahan, atau yang ada pesanan bulan ini
        $branches = $this->visibleOutlets()
            ->filter(fn ($b) => $b->accepts_orders || isset($byOutlet[$b->id]))
            ->map(fn ($b) => ['outlet' => $b, 'stats' => $byOutlet[$b->id] ?? $empty()])
            ->values();

        $totals = $empty();
        foreach ($branches as $r) {
            foreach ($totals as $k => $v) {
                $totals[$k] += $r['stats'][$k];
            }
        }

        return view('livewire.tempahan.branch-dashboard', [
            'branches' => $branches,
            'totals' => $totals,
            'buckets' => self::BUCKETS,
            'monthLabel' => $start->translatedFormat('F Y'),
        ]);
    }
}
