<?php

namespace App\Services;

use App\Models\CashRecord;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Staff;

/**
 * Semua pengiraan kewangan outlet untuk satu bulan (YYYY-MM).
 * Meniru 1:1 fungsi calc() + helper dalam prototaip `pakmaon-outlet.html`.
 */
class OutletMetrics
{
    public function __construct(
        public Outlet $outlet,
        public string $month,
    ) {}

    public static function make(Outlet $outlet, string $month): self
    {
        return new self($outlet, $month);
    }

    /**
     * Pulangkan semua nilai teragregat untuk bulan dipilih (PRD §5).
     *
     * @return array<string,mixed>
     */
    public static function for(Outlet $outlet, string $month): array
    {
        return (new self($outlet, $month))->toArray();
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $cats = config('outlet.categories');
        [$year, $mon] = array_pad(explode('-', $this->month), 2, null);

        // ---- Resit (kos berubah) untuk bulan ini ----
        $receipts = $this->outlet->receipts()
            ->whereYear('date', (int) $year)
            ->whereMonth('date', (int) $mon)
            ->get(['category', 'amount']);

        $varByCat = [];
        foreach ($cats as $id => $c) {
            $varByCat[$id] = 0.0;
        }
        $varTotal = 0.0;
        $cogs = 0.0;
        foreach ($receipts as $r) {
            $amt = (float) $r->amount;
            $varByCat[$r->category] = ($varByCat[$r->category] ?? 0) + $amt;
            $varTotal += $amt;
            if (! empty($cats[$r->category]['cogs'])) {
                $cogs += $amt;
            }
        }

        // ---- Kos tetap ----
        $otherFixed = (float) $this->outlet->fixedCosts()->sum('amount');
        $staffCost = $this->staffCost();
        $fixedTotal = $otherFixed + $staffCost;

        // ---- Jualan bulan ini ----
        $sales = (float) $this->outlet->sales()
            ->whereYear('date', (int) $year)
            ->whereMonth('date', (int) $mon)
            ->sum('amount');

        // ---- Agregat ----
        $expenses = $varTotal + $fixedTotal;
        $net = $sales - $expenses;
        $capex = (float) $this->outlet->capexItems()->sum('amount');

        $foodCostPct = $sales > 0 ? $cogs / $sales * 100 : 0.0;
        $marginPct = $sales > 0 ? $net / $sales * 100 : 0.0;
        $varRatio = $sales > 0 ? $varTotal / $sales : 0.0;
        $beSales = (1 - $varRatio) > 0 ? $fixedTotal / (1 - $varRatio) : 0.0;
        $roiMonths = $net > 0 ? $capex / $net : INF;

        // ---- Sasaran / ROI ----
        $targetSales = (float) $this->outlet->target_sales;
        $targetMargin = (float) $this->outlet->target_margin;
        $targetRoiMonths = (int) $this->outlet->target_roi_months;

        $targetNet = $targetSales * ($targetMargin / 100);
        $targetRoi = $targetNet > 0 ? $capex / $targetNet : INF;
        $neededNet = $targetRoiMonths > 0 ? $capex / $targetRoiMonths : 0.0;
        $neededSales = $targetMargin > 0 ? $neededNet / ($targetMargin / 100) : 0.0;

        return [
            'varByCat' => $varByCat,
            'varTotal' => $varTotal,
            'cogs' => $cogs,
            'otherFixed' => $otherFixed,
            'staffCost' => $staffCost,
            'fixedTotal' => $fixedTotal,
            'sales' => $sales,
            'expenses' => $expenses,
            'net' => $net,
            'capex' => $capex,
            'foodCostPct' => $foodCostPct,
            'marginPct' => $marginPct,
            'varRatio' => $varRatio,
            'beSales' => $beSales,
            'roiMonths' => $roiMonths,
            'targetSales' => $targetSales,
            'targetMargin' => $targetMargin,
            'targetRoiMonths' => $targetRoiMonths,
            'targetNet' => $targetNet,
            'targetRoi' => $targetRoi,
            'neededNet' => $neededNet,
            'neededSales' => $neededSales,
        ];
    }

    /** Jumlah kos majikan semua staff (gaji + EPF + SOCSO). */
    public function staffCost(): float
    {
        $epf = (float) $this->outlet->epf_pct;
        $socso = (float) $this->outlet->socso_pct;

        return (float) $this->outlet->staff
            ->sum(fn (Staff $s) => self::staffTotal($s, $epf, $socso));
    }

    /**
     * Kos majikan sebenar bagi seorang staff = gaji + EPF% + SOCSO% (jika diaktifkan).
     * Padan dengan staffTotal() prototaip.
     */
    public static function staffTotal(Staff $staff, float $epfPct, float $socsoPct): float
    {
        $g = (float) $staff->salary;
        if (! $staff->epf_socso) {
            return $g;
        }

        return $g + $g * ($epfPct / 100) + $g * ($socsoPct / 100);
    }

    /** Jumlah kos bahan satu menu. */
    public static function menuCost(Menu $menu): float
    {
        return (float) $menu->ingredients->sum(fn ($i) => (float) $i->cost);
    }

    /**
     * Statistik food cost satu menu.
     *
     * @return array{cost:float,gp:float,foodCostPct:float,gpPct:float}
     */
    public static function menuStats(Menu $menu): array
    {
        $price = (float) $menu->price;
        $cost = self::menuCost($menu);
        $gp = $price - $cost;

        return [
            'cost' => $cost,
            'gp' => $gp,
            'foodCostPct' => $price > 0 ? $cost / $price * 100 : 0.0,
            'gpPct' => $price > 0 ? $gp / $price * 100 : 0.0,
        ];
    }

    /** Purata GP% semua menu sesebuah outlet. */
    public static function averageGp(iterable $menus): float
    {
        $menus = collect($menus);
        if ($menus->isEmpty()) {
            return 0.0;
        }

        return (float) $menus->avg(fn (Menu $m) => self::menuStats($m)['gpPct']);
    }

    /** Petty cash: sepatutnya = float + jualan tunai − bayaran tunai. */
    public static function cashExpected(CashRecord $r): float
    {
        return (float) $r->float_start + (float) $r->cash_sales - (float) $r->cash_payments;
    }

    /** Petty cash: beza = kiraan sebenar − sepatutnya. */
    public static function cashDiff(CashRecord $r): float
    {
        return (float) $r->actual_count - self::cashExpected($r);
    }
}
