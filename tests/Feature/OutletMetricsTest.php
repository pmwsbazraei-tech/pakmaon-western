<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Staff;
use App\Models\User;
use App\Services\OutletMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function outlet(array $overrides = []): Outlet
    {
        $user = User::factory()->create();

        return Outlet::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Pak Maon Western',
            'target_sales' => 30000,
            'target_margin' => 20,
            'target_roi_months' => 12,
            'epf_pct' => 13,
            'socso_pct' => 1.75,
        ], $overrides));
    }

    public function test_staff_total_applies_epf_and_socso_only_when_enabled(): void
    {
        $withKwsp = new Staff(['salary' => 3000, 'epf_socso' => true]);
        $withoutKwsp = new Staff(['salary' => 1700, 'epf_socso' => false]);

        // 3000 + 13% + 1.75% = 3000 * 1.1475 = 3442.5
        $this->assertEqualsWithDelta(3442.5, OutletMetrics::staffTotal($withKwsp, 13, 1.75), 0.001);
        // tiada EPF/SOCSO → gaji sahaja
        $this->assertEqualsWithDelta(1700.0, OutletMetrics::staffTotal($withoutKwsp, 13, 1.75), 0.001);
    }

    public function test_default_staff_cost_matches_prototype(): void
    {
        $outlet = $this->outlet();
        $outlet->staff()->createMany([
            ['name' => 'Head Cook', 'position' => 'Head Cook', 'salary' => 3000, 'type' => 'Sepenuh Masa', 'epf_socso' => true],
            ['name' => 'Cook', 'position' => 'Cook', 'salary' => 2200, 'type' => 'Sepenuh Masa', 'epf_socso' => true],
            ['name' => 'Helper', 'position' => 'Helper', 'salary' => 1700, 'type' => 'Separuh Masa', 'epf_socso' => false],
            ['name' => 'Cashier', 'position' => 'Cashier', 'salary' => 1800, 'type' => 'Sepenuh Masa', 'epf_socso' => true],
        ]);

        $m = OutletMetrics::for($outlet->fresh(), '2026-08');

        // 3442.5 + 2524.5 + 1700 + 2065.5 = 9732.5
        $this->assertEqualsWithDelta(9732.5, $m['staffCost'], 0.001);
        $this->assertEqualsWithDelta(9732.5, $m['fixedTotal'], 0.001); // tiada kos tetap lain
    }

    public function test_chicken_chop_food_cost_and_gp(): void
    {
        $outlet = $this->outlet();
        $menu = $outlet->menus()->create(['name' => 'Chicken Chop', 'price' => 15.90]);
        $menu->ingredients()->createMany([
            ['name' => 'Isi ayam', 'cost' => 3.50],
            ['name' => 'Kentang & sayur', 'cost' => 1.20],
            ['name' => 'Sos black pepper', 'cost' => 0.80],
            ['name' => 'Minyak & rempah', 'cost' => 0.50],
        ]);

        $stats = OutletMetrics::menuStats($menu->fresh('ingredients'));

        $this->assertEqualsWithDelta(6.00, $stats['cost'], 0.001);
        $this->assertEqualsWithDelta(9.90, $stats['gp'], 0.001);
        $this->assertEqualsWithDelta(37.7, $stats['foodCostPct'], 0.05);
        $this->assertEqualsWithDelta(62.3, $stats['gpPct'], 0.05);
    }

    public function test_petty_cash_expected_and_diff(): void
    {
        $outlet = $this->outlet();
        $record = $outlet->cashRecords()->create([
            'date' => '2026-08-01',
            'float_start' => 100,
            'cash_sales' => 850,
            'cash_payments' => 50,
            'actual_count' => 895,
        ]);

        $this->assertEqualsWithDelta(900.0, OutletMetrics::cashExpected($record), 0.001);
        $this->assertEqualsWithDelta(-5.0, OutletMetrics::cashDiff($record), 0.001);
    }

    public function test_aggregates_cogs_break_even_and_roi(): void
    {
        $outlet = $this->outlet();

        // Jualan bulan ini
        $outlet->sales()->create(['date' => '2026-08-05', 'amount' => 10000]);

        // Resit: bahan (COGS) 3000, packaging (COGS) 500, utiliti (bukan COGS) 1000
        $outlet->receipts()->createMany([
            ['date' => '2026-08-03', 'category' => 'bahan', 'amount' => 3000],
            ['date' => '2026-08-04', 'category' => 'packaging', 'amount' => 500],
            ['date' => '2026-08-06', 'category' => 'utiliti', 'amount' => 1000],
            // resit bulan lain — mesti diabaikan
            ['date' => '2026-07-10', 'category' => 'bahan', 'amount' => 9999],
        ]);

        $outlet->fixedCosts()->create(['name' => 'Sewa', 'group' => 'Sewa', 'amount' => 3500]);
        $outlet->capexItems()->create(['name' => 'Renovasi', 'amount' => 25000]);

        $m = OutletMetrics::for($outlet->fresh(), '2026-08');

        $this->assertEqualsWithDelta(4500.0, $m['varTotal'], 0.001);   // 3000+500+1000
        $this->assertEqualsWithDelta(3500.0, $m['cogs'], 0.001);       // bahan+packaging sahaja
        $this->assertEqualsWithDelta(3500.0, $m['otherFixed'], 0.001);
        $this->assertEqualsWithDelta(3500.0, $m['fixedTotal'], 0.001); // otherFixed 3500 + staff 0
        $this->assertEqualsWithDelta(8000.0, $m['expenses'], 0.001);   // varTotal 4500 + fixed 3500
        $this->assertEqualsWithDelta(2000.0, $m['net'], 0.001);        // 10000 - 8000
        $this->assertEqualsWithDelta(35.0, $m['foodCostPct'], 0.001);  // 3500/10000
        $this->assertEqualsWithDelta(20.0, $m['marginPct'], 0.001);    // 2000/10000

        // break-even = fixedTotal / (1 - varRatio); varRatio = 4500/10000 = 0.45
        // 3500 / 0.55 = 6363.63...
        $this->assertEqualsWithDelta(6363.636, $m['beSales'], 0.01);
        // roiMonths = capex/net = 25000/2000 = 12.5
        $this->assertEqualsWithDelta(12.5, $m['roiMonths'], 0.001);
    }

    public function test_roi_is_infinite_when_no_profit(): void
    {
        $outlet = $this->outlet();
        $outlet->capexItems()->create(['name' => 'Renovasi', 'amount' => 25000]);

        $m = OutletMetrics::for($outlet->fresh(), '2026-08');

        $this->assertInfinite($m['roiMonths']); // net <= 0 → tak balik modal
    }

    public function test_target_helpers(): void
    {
        $outlet = $this->outlet(['target_sales' => 30000, 'target_margin' => 20, 'target_roi_months' => 12]);
        $outlet->capexItems()->create(['name' => 'Renovasi', 'amount' => 72000]);

        $m = OutletMetrics::for($outlet->fresh(), '2026-08');

        $this->assertEqualsWithDelta(6000.0, $m['targetNet'], 0.001);   // 30000 * 20%
        $this->assertEqualsWithDelta(12.0, $m['targetRoi'], 0.001);     // 72000 / 6000
        $this->assertEqualsWithDelta(6000.0, $m['neededNet'], 0.001);   // 72000 / 12
        $this->assertEqualsWithDelta(30000.0, $m['neededSales'], 0.001); // 6000 / 20%
    }
}
