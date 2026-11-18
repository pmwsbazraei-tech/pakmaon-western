<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DefaultOutletData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Regresi: tukar "Bulan laporan" di Dashboard (wire:model.live="month") dahulu
 * menghasilkan 500 "Undefined variable $outlet" kerana EnsureOutlet tidak
 * dijalankan pada permintaan /livewire/update.
 */
class LivewireOutletContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_report_month_on_dashboard_rerenders_with_outlet_context(): void
    {
        $user = User::factory()->create();
        $outlet = DefaultOutletData::provision($user);
        DB::table('sales')->insert(['outlet_id' => $outlet->id, 'date' => '2026-09-05', 'amount' => 4321.5, 'created_at' => now(), 'updated_at' => now()]);

        $html = $this->actingAs($user)->get(route('dashboard', ['month' => '2026-08']))->assertOk()->getContent();
        // Halaman ada beberapa komponen (cth. pemilih outlet di sidebar): ambil snapshot Dashboard
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $all);
        $snapshot = collect($all[1])->map(fn ($s) => html_entity_decode($s, ENT_QUOTES))
            ->first(fn ($s) => json_decode($s, true)['memo']['name'] === 'dashboard');
        $this->assertNotNull($snapshot, 'Snapshot Dashboard tidak dijumpai');

        // Pastikan $outlet tidak tertinggal daripada permintaan halaman pertama
        View::share('outlet', null);

        $res = $this->withHeaders(['X-Livewire' => 'true'])->postJson('/livewire/update', [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => ['month' => '2026-09'],
                'calls' => [],
            ]],
        ]);

        $res->assertOk();
        $this->assertStringContainsString('RM4,321.50', $res->json('components.0.effects.html'));
    }
}
