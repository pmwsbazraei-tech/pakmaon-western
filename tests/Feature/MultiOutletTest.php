<?php

namespace Tests\Feature;

use App\Livewire\OutletSwitcher;
use App\Models\User;
use App\Support\DefaultOutletData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiOutletTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_blank_outlet_and_switch(): void
    {
        $user = User::factory()->create();
        $first = DefaultOutletData::provision($user);

        $this->actingAs($user);

        // Cipta outlet baharu (kosong)
        Livewire::test(OutletSwitcher::class)
            ->call('createOutlet')
            ->assertRedirect(route('settings'));

        $this->assertSame(2, $user->outlets()->count());

        $new = $user->outlets()->where('name', 'Outlet Baru')->first();
        $this->assertNotNull($new);

        // Outlet baharu mesti KOSONG (tiada data)
        $this->assertSame(0, $new->staff()->count());
        $this->assertSame(0, $new->menus()->count());
        $this->assertSame(0, $new->equipment()->count());

        // Ia jadi aktif
        $this->assertSame($new->id, session('active_outlet_id'));
        $this->assertSame($new->id, $user->currentOutlet()->id);

        // Tukar balik ke outlet pertama
        Livewire::test(OutletSwitcher::class)
            ->call('switchTo', $first->id)
            ->assertRedirect(route('dashboard'));

        $this->assertSame($first->id, session('active_outlet_id'));
        $this->assertSame($first->id, $user->currentOutlet()->id);
    }

    public function test_cannot_switch_to_another_users_outlet(): void
    {
        $me = User::factory()->create();
        DefaultOutletData::provision($me);

        $other = User::factory()->create();
        $otherOutlet = DefaultOutletData::provision($other);

        $this->actingAs($me);
        session(['active_outlet_id' => $me->currentOutlet()->id]);

        Livewire::test(OutletSwitcher::class)->call('switchTo', $otherOutlet->id);

        // Session tak boleh bertukar ke outlet orang lain
        $this->assertNotSame($otherOutlet->id, session('active_outlet_id'));
    }

    public function test_data_is_isolated_between_outlets(): void
    {
        $user = User::factory()->create();
        $a = DefaultOutletData::provision($user);
        $b = $user->outlets()->create(['name' => 'Pasir Gudang']);

        $a->sales()->create(['date' => '2026-08-01', 'amount' => 5000]);
        $b->sales()->create(['date' => '2026-08-01', 'amount' => 999]);

        $this->assertEqualsWithDelta(5000, (float) $a->sales()->sum('amount'), 0.001);
        $this->assertEqualsWithDelta(999, (float) $b->sales()->sum('amount'), 0.001);
        $this->assertSame(1, $a->sales()->count());
        $this->assertSame(1, $b->sales()->count());
    }
}
