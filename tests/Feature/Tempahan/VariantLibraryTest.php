<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\ProductEditor;
use App\Livewire\Tempahan\Variants;
use App\Models\User;
use App\Models\VariantGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

class VariantLibraryTest extends PmwTestCase
{
    private function makeGroup(string $name, array $options): VariantGroup
    {
        $c = Livewire::actingAs($this->owner)->test(Variants::class)->set('groupName', $name)->call('saveGroup')->assertHasNoErrors();
        $group = VariantGroup::where('name', $name)->firstOrFail();
        foreach ($options as [$opt, $price]) {
            $c->set('o.name', $opt)->set('o.price', $price)->call('saveOption')->assertHasNoErrors();
        }

        return $group;
    }

    public function test_product_ticks_groups_and_gets_all_their_options(): void
    {
        $chicken = $this->makeGroup('Chicken', [['Chicken 1', '10.00'], ['Chicken 2', '11.00'], ['Chicken 3', '12.00']]);
        $lamb = $this->makeGroup('Lamb', [['Lamb 1', '20.00'], ['Lamb 2', '21.00']]);

        $toast = $this->toast();
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $toast])
            ->set('groupIds', [(string) $chicken->id, (string) $lamb->id])->call('save')->assertHasNoErrors();

        $toast->refresh();
        $this->assertTrue($toast->has_variants);
        $this->assertSame(['Chicken 1', 'Chicken 2', 'Chicken 3', 'Lamb 1', 'Lamb 2'], $toast->activeVariants()->pluck('name')->all());

        // Group yang sama boleh ditick pada produk lain
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $this->talam()])
            ->set('groupIds', [(string) $chicken->id])->call('save')->assertHasNoErrors();
        $this->assertSame(['Chicken 1', 'Chicken 2', 'Chicken 3'], $this->talam()->activeVariants()->pluck('name')->all());

        // Tambah pilihan / tukar harga dalam group: semua produk ikut
        $c = Livewire::actingAs($this->owner)->test(Variants::class)->call('openOption', $chicken->id)
            ->set('o.name', 'Chicken 4')->set('o.price', '13.00')->call('saveOption')->assertHasNoErrors();
        $opt1 = $chicken->activeVariants()->where('name', 'Chicken 1')->first();
        $c->call('openOption', $chicken->id, $opt1->id)->set('o.price', '9.50')->call('saveOption')->assertHasNoErrors();

        $this->assertSame(6, $toast->activeVariants()->count());
        $this->assertSame([950, 950], $opt1->productVariants()->pluck('price_cents')->all());

        // Pelanggan: harga dari pelayan, nama group dihantar ke halaman
        $line = $toast->activeVariants()->where('name', 'Chicken 1')->first();
        $order = $this->placeOrder($this->purchasePayload(['items' => [['product_id' => $toast->id, 'variant_id' => $line->id, 'quantity' => 2]]]));
        $this->assertSame(1900, $order->items->first()->line_total_cents);
        $this->assertStringContainsString('Lamb', $this->get('/p/pak-maon-western')->assertOk()->getContent());
    }

    public function test_delete_option_and_group(): void
    {
        $talam = $this->talam();
        $group = $talam->variantGroups()->firstOrFail();
        $this->assertSame(3, $talam->activeVariants()->count());

        $first = $group->activeVariants()->first();
        Livewire::actingAs($this->owner)->test(Variants::class)->call('deleteOption', $group->id, $first->id);
        $this->assertSame(2, $talam->activeVariants()->count());

        // Padam group: produk tanpa variant & tanpa harga disembunyikan
        Livewire::actingAs($this->owner)->test(Variants::class)->call('deleteGroup', $group->id);
        $talam->refresh();
        $this->assertSame(0, $talam->activeVariants()->count());
        $this->assertFalse($talam->has_variants);
        $this->assertTrue($talam->is_hidden);
    }

    public function test_drag_sorting_options_updates_product_order(): void
    {
        $talam = $this->talam();
        $group = $talam->variantGroups()->firstOrFail();
        $ids = $group->activeVariants()->pluck('id')->all();

        Livewire::actingAs($this->owner)->test(Variants::class)->call('sortOptions', $group->id, array_reverse($ids));

        $this->assertSame(['Crispy Chicken Thigh', 'Chicken Grill', 'Chicken Crispy Breast'], $talam->activeVariants()->pluck('name')->all());
    }

    public function test_untick_group_requires_price(): void
    {
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $this->talam()])
            ->set('groupIds', [])->set('p.price', '')->call('save')->assertHasErrors('p.price');
    }

    public function test_cannot_use_another_shops_group(): void
    {
        $other = VariantGroup::create(['user_id' => User::factory()->create()->id, 'name' => 'Asing']);

        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $this->toast()])
            ->set('groupIds', [(string) $other->id])->call('save')->assertHasErrors('groupIds.0');

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->owner)->test(Variants::class)->call('editGroup', $other->id);
    }

    public function test_variants_page_shows_groups_and_is_in_sidebar(): void
    {
        $this->actingAs($this->owner)->get(route('tempahan.variants'))->assertOk()->assertSee('Pilihan Ayam')->assertSee('Chicken Grill')
            // Butang lihat produk + pautan ke produk yang menggunakan group
            ->assertSee('Lihat produk (1)')->assertSee(route('tempahan.products.edit', $this->talam()), false);
        $this->actingAs($this->owner)->get(route('dashboard'))->assertSee(route('tempahan.variants'), false);
    }
}
