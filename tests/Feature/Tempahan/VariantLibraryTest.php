<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\ProductEditor;
use App\Livewire\Tempahan\Variants;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

class VariantLibraryTest extends PmwTestCase
{
    public function test_one_variant_can_be_ticked_on_many_products_and_price_syncs(): void
    {
        Livewire::actingAs($this->owner)->test(Variants::class)
            ->set('f.name', 'Extra Cheese')->set('f.price', '2.50')->call('save')->assertHasNoErrors();
        $cheese = Variant::where('name', 'Extra Cheese')->firstOrFail();

        // Tick pada dua produk
        foreach ([$this->toast(), $this->talam()] as $product) {
            Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $product])
                ->set('variantIds', array_merge(
                    $product->variants()->where('is_active', true)->pluck('variant_id')->map(fn ($i) => (string) $i)->all(),
                    [(string) $cheese->id],
                ))
                ->call('save')->assertHasNoErrors();
        }

        $this->assertTrue($this->toast()->fresh()->has_variants);
        $this->assertSame(2, $cheese->productVariants()->where('is_active', true)->count());

        // Tukar harga di senarai Variant: semua produk ikut
        Livewire::actingAs($this->owner)->test(Variants::class)
            ->call('edit', $cheese->id)->set('f.price', '3.00')->call('save')->assertHasNoErrors();
        $this->assertSame([300, 300], $cheese->productVariants()->pluck('price_cents')->all());

        // Pesanan guna harga variant dari pelayan
        $line = $this->toast()->variants()->where('variant_id', $cheese->id)->first();
        $order = $this->placeOrder($this->purchasePayload(['items' => [['product_id' => $this->toast()->id, 'variant_id' => $line->id, 'quantity' => 2]]]));
        $this->assertSame('Extra Cheese', $order->items->first()->variant_name);
        $this->assertSame(600, $order->items->first()->line_total_cents);
    }

    public function test_untick_and_delete_variant(): void
    {
        $talam = $this->talam();
        $ids = $talam->variants()->where('is_active', true)->pluck('variant_id');
        $this->assertCount(3, $ids);

        // Untick satu
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $talam])
            ->set('variantIds', $ids->slice(1)->map(fn ($i) => (string) $i)->values()->all())
            ->call('save')->assertHasNoErrors();
        $this->assertSame(2, $talam->activeVariants()->count());

        // Padam semua variant: produk tanpa variant disembunyikan
        foreach ($ids as $id) {
            Livewire::actingAs($this->owner)->test(Variants::class)->call('delete', $id);
        }
        $this->assertSame(0, $talam->activeVariants()->count());
        $this->assertTrue($talam->fresh()->is_hidden);
        $this->assertSame(0, Variant::where('user_id', $this->owner->id)->where('is_active', true)->count());
    }

    public function test_cannot_tick_another_shops_variant(): void
    {
        $other = Variant::create(['user_id' => User::factory()->create()->id, 'name' => 'Asing', 'price_cents' => 100]);

        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $this->toast()])
            ->set('variantIds', [(string) $other->id])->call('save')->assertHasErrors('variantIds.0');

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->owner)->test(Variants::class)->call('edit', $other->id);
    }

    public function test_variants_page_is_owner_only_and_in_sidebar(): void
    {
        $this->actingAs($this->owner)->get(route('tempahan.variants'))->assertOk()->assertSee('Chicken Grill');
        $this->actingAs($this->owner)->get(route('dashboard'))->assertSee(route('tempahan.variants'), false);
    }
}
