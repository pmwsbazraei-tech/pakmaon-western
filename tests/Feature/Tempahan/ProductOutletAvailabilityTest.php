<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\ProductEditor;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class ProductOutletAvailabilityTest extends PmwTestCase
{
    public function test_ticked_outlets_sell_the_product(): void
    {
        $toast = $this->toast();
        $all = $this->owner->outlets()->pluck('id')->map(fn ($i) => (string) $i)->all();

        // Lalai: semua outlet ditick
        $c = Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $toast]);
        $this->assertEqualsCanonicalizing($all, $c->get('availableOutlets'));

        // Untick Melaka sahaja → Melaka direkod tidak menjual
        $melaka = (string) $this->outlet('melaka')->id;
        $c->set('availableOutlets', array_values(array_diff($all, [$melaka])))->call('save')->assertHasNoErrors();
        $this->assertSame([(int) $melaka], DB::table('outlet_product')->where('product_id', $toast->id)->where('is_available', false)->pluck('outlet_id')->map(fn ($i) => (int) $i)->all());

        // Dibuka semula: Melaka tidak ditick
        $c2 = Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $toast->fresh()]);
        $this->assertNotContains($melaka, $c2->get('availableOutlets'));

        // Pilih semua → tiada rekod "tidak menjual"
        $c2->set('availableOutlets', $all)->call('save')->assertHasNoErrors();
        $this->assertSame(0, DB::table('outlet_product')->where('product_id', $toast->id)->count());
    }

    public function test_editor_has_select_all_control(): void
    {
        $this->actingAs($this->owner)->get(route('tempahan.products.edit', $this->toast()))->assertOk()->assertSee('Pilih semua outlet');
    }
}
