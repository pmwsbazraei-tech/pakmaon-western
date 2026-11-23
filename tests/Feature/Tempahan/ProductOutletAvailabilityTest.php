<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\ProductEditor;
use App\Models\Product;
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

    public function test_owner_can_delete_product_and_past_orders_keep_their_items(): void
    {
        $toast = $this->toast();
        $order = $this->placeOrder($this->purchasePayload());

        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $toast])
            ->call('deleteProduct')->assertRedirect(route('tempahan.products'));

        $this->assertNull($toast->fresh());
        $item = $order->items()->first();
        $this->assertNull($item->product_id);
        $this->assertSame('Double Butter Kaya Toast', $item->product_name);
        $this->actingAs($this->owner)->get(route('tempahan.orders.show', $order))->assertOk();
    }

    public function test_new_product_is_auto_ticked_on_landing_pages_of_its_outlets(): void
    {
        $form = $this->form(); // melayan beberapa outlet
        $this->assertTrue($form->outlets()->exists());

        Livewire::actingAs($this->owner)->test(ProductEditor::class)
            ->set('p.name', 'Fries Baru')->set('p.price', '4.00')->call('save')->assertHasNoErrors();

        $fries = Product::where('name', 'Fries Baru')->firstOrFail();
        $this->assertTrue($form->products()->whereKey($fries->id)->exists());

        // Selepas disimpan: butang cipta produk baharu dipaparkan; borang baharu tiada butang itu
        $this->actingAs($this->owner)->get(route('tempahan.products.edit', $fries))->assertOk()
            ->assertSee('Cipta produk baharu')->assertSee(route('tempahan.products.create'), false);
        $this->actingAs($this->owner)->get(route('tempahan.products.create'))->assertOk()->assertDontSee('Cipta produk baharu');
    }

    public function test_untick_on_landing_page_is_kept_until_outlet_is_newly_ticked(): void
    {
        $form = $this->form();
        $toast = $this->toast();
        $formOutlet = (string) $form->outlets()->first()->id;
        $all = $this->owner->outlets()->pluck('id')->map(fn ($i) => (string) $i)->all();

        // Untick produk di landing page (cth. habis stok)
        $form->products()->detach($toast->id);

        // Simpan produk tanpa ubah outlet: kekal tidak ditick
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $toast])->call('save')->assertHasNoErrors();
        $this->assertFalse($form->products()->whereKey($toast->id)->exists());

        // Untick outlet itu, kemudian tick semula → produk ditick semula di landing page
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $toast->fresh()])
            ->set('availableOutlets', array_values(array_diff($all, [$formOutlet])))->call('save')->assertHasNoErrors();
        $this->assertFalse($form->products()->whereKey($toast->id)->exists());

        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $toast->fresh()])
            ->set('availableOutlets', $all)->call('save')->assertHasNoErrors();
        $this->assertTrue($form->products()->whereKey($toast->id)->exists());
    }

    public function test_editor_has_select_all_control(): void
    {
        $this->actingAs($this->owner)->get(route('tempahan.products.edit', $this->toast()))->assertOk()->assertSee('Pilih semua outlet');
    }
}
