<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\BranchDashboard;
use App\Livewire\Tempahan\Orders;
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

        // Nombor dashboard ialah pautan ke senarai pesanan (bulan, status & cawangan sama)
        $c->assertSeeHtml('status=deposit')->assertSeeHtml('outlet='.$tangkak->id);
        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();
        foreach (['new' => 2, 'deposit' => 1, 'paid' => 1, 'cancelled' => 1] as $status => $count) {
            $list = Livewire::actingAs($this->owner)->withQueryParams(['status' => $status, 'outlet' => (string) $tangkak->id, 'from' => $from, 'to' => $to])
                ->test(Orders::class);
            $this->assertSame($count, $list->viewData('orders')->total(), "status {$status}");
        }
        $all = Livewire::actingAs($this->owner)->withQueryParams(['status' => 'new', 'from' => $from, 'to' => $to])->test(Orders::class);
        $this->assertSame(3, $all->viewData('orders')->total()); // semua cawangan

        // Bulan lain: tiada pesanan
        $c->set('month', '2026-10');
        $this->assertSame(0, $c->viewData('totals')['total']);
    }

    public function test_sidebar_only_has_dashboard_and_online_booking(): void
    {
        $this->actingAs($this->owner)->get(route('dashboard'))->assertOk()
            ->assertSee('Ringkasan Tempahan')->assertSee('Landing Page')
            ->assertSee('BM')->assertSee('EN')
            ->assertDontSee('Scan Resit')->assertDontSee('Petty Cash')->assertDontSee('ROI &amp; Target', false)->assertSee('Outlet aktif')->assertSee('+ Outlet Baru');
    }

    public function test_language_switch_between_malay_and_english(): void
    {
        $this->actingAs($this->owner)->from(route('dashboard'))
            ->get(route('locale.switch', 'en'))->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Online Orders')->assertSee('Log Out')->assertSee('Active outlet')->assertDontSee('Log Keluar');

        $this->get(route('locale.switch', 'ms'));
        $this->get(route('dashboard'))->assertSee('Tempahan Online')->assertSee('Log Keluar');

        $this->get('/bahasa/fr')->assertNotFound();
    }
}
