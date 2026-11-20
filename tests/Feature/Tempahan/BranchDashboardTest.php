<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\BranchDashboard;
use Livewire\Livewire;

class BranchDashboardTest extends PmwTestCase
{
    public function test_dashboard_counts_orders_by_branch_and_status(): void
    {
        $tangkak = $this->outlet('tangkak');
        $place = fn () => $this->placeOrder($this->purchasePayload(['outlet_id' => $tangkak->id]));

        $place();                                                                          // Baharu
        $place();                                                                          // Baharu
        $place()->update(['payment_status' => 'partial', 'fulfillment_status' => 'deposit', 'deposit_cents' => 100]);
        $paid = $place();
        $paid->update(['payment_status' => 'paid', 'fulfillment_status' => 'paid']);
        $place()->update(['fulfillment_status' => 'cancelled']);
        $this->placeOrder($this->purchasePayload());                                       // Melaka: Baharu

        $c = Livewire::actingAs($this->owner)->test(BranchDashboard::class);
        $branches = collect($c->viewData('branches'))->keyBy(fn ($r) => $r['outlet']->name);

        $t = $branches['Pak Maon Western Tangkak']['stats'];
        $this->assertSame([2, 1, 1, 1, 5], [$t['new'], $t['deposit'], $t['paid'], $t['cancelled'], $t['total']]);
        $this->assertSame($paid->total_cents, $t['sales_cents']);
        $this->assertSame(1, $branches['Pak Maon Western Melaka']['stats']['new']);
        $this->assertSame(6, $c->viewData('totals')['total']);
        $c->assertSee('Pak Maon Western Tangkak')->assertSee('Dibatalkan');

        // Bulan lain: tiada pesanan
        $c->set('month', '2026-10');
        $this->assertSame(0, $c->viewData('totals')['total']);
    }

    public function test_sidebar_only_has_dashboard_and_online_booking(): void
    {
        $this->actingAs($this->owner)->get(route('dashboard'))->assertOk()
            ->assertSee('Ringkasan Tempahan')->assertSee('Landing Page')
            ->assertDontSee('Scan Resit')->assertDontSee('Petty Cash')->assertDontSee('ROI &amp; Target', false)->assertDontSee('+ Outlet Baru');
    }
}
