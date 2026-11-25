<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\ProductEditor;
use App\Livewire\Tempahan\Variants;
use App\Models\Order;
use App\Models\VariantGroup;
use Livewire\Livewire;

class AddonRulesTest extends PmwTestCase
{
    /** Toast RM5.30 dengan group "Sos" (Cheezy RM1.80, Blackpepper RM1.20, Mushroom RM1.50). */
    private function sauceGroup(bool $required, int $max): VariantGroup
    {
        $g = VariantGroup::create(['user_id' => $this->owner->id, 'name' => 'Sos', 'is_required' => $required, 'max_select' => $max]);
        foreach ([['Cheezy', 180], ['Blackpepper', 120], ['Mushroom', 150]] as $i => [$n, $p]) {
            $g->variants()->create(['name' => $n, 'price_cents' => $p, 'sort' => $i]);
        }
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $this->toast()])
            ->set('groupIds', [(string) $g->id])->call('save')->assertHasNoErrors();

        return $g;
    }

    private function opt(string $name): int
    {
        return $this->toast()->activeVariants()->where('name', $name)->value('id');
    }

    private function order(array $options)
    {
        return $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload([
            'items' => [['product_id' => $this->toast()->id, 'options' => $options, 'quantity' => 1]],
        ]));
    }

    public function test_optional_group_can_be_skipped_and_addons_add_to_price(): void
    {
        $this->sauceGroup(required: false, max: 2);

        $this->order([])->assertSuccessful();                                   // langkau: harga asas sahaja
        $res = $this->order([$this->opt('Cheezy'), $this->opt('Blackpepper')])->assertSuccessful();

        $order = Order::where('reference', $res->json('reference'))->first() ?? Order::latest('id')->first();
        $this->assertSame(530 + 180 + 120, $order->items->first()->unit_price_cents);

        // Optional: boleh pilih semua pilihan walaupun max_select lama lebih kecil
        $this->order([$this->opt('Cheezy'), $this->opt('Blackpepper'), $this->opt('Mushroom')])->assertSuccessful();
    }

    public function test_required_group_must_have_a_choice(): void
    {
        $this->sauceGroup(required: true, max: 1);

        $this->order([])->assertStatus(422);
        $this->order([$this->opt('Cheezy')])->assertSuccessful();
    }

    public function test_max_selection_is_enforced(): void
    {
        $this->sauceGroup(required: true, max: 2);

        $this->order([$this->opt('Cheezy'), $this->opt('Blackpepper'), $this->opt('Mushroom')])->assertStatus(422);
        $this->order([$this->opt('Cheezy'), $this->opt('Mushroom')])->assertSuccessful();
    }

    public function test_owner_sets_rules_on_variants_page_and_max_is_capped(): void
    {
        $g = $this->sauceGroup(required: true, max: 1);

        Livewire::actingAs($this->owner)->test(Variants::class)
            ->call('setRequired', $g->id, false)
            ->call('setMax', $g->id, 99);

        $g->refresh();
        $this->assertFalse($g->is_required);
        $this->assertSame(3, $g->max_select); // tidak melebihi bilangan pilihan
        $this->assertSame('Pilihan · sehingga 3', $g->ruleLabel());

        // Halaman pelanggan menerima peraturan group
        $html = $this->get('/p/pak-maon-western')->assertOk()->getContent();
        $this->assertStringContainsString('sehingga 3', $html);
    }

    public function test_closed_option_is_shown_unavailable_and_cannot_be_ordered(): void
    {
        $g = $this->sauceGroup(required: true, max: 1);
        $cheezy = $g->variants()->where('name', 'Cheezy')->first();

        Livewire::actingAs($this->owner)->test(Variants::class)
            ->call('toggleAvailable', $g->id, $cheezy->id)
            ->assertSee('Tidak tersedia');
        $this->assertFalse($cheezy->fresh()->is_available);

        // Pelanggan masih nampak pilihan itu, ditanda tidak tersedia; pilihan lain boleh dipesan
        $html = $this->get('/p/pak-maon-western')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/Cheezy.{0,80}available.{0,12}false/s', $html);
        $this->order([$this->opt('Cheezy')])->assertStatus(422);
        $this->order([$this->opt('Blackpepper')])->assertSuccessful();

        // Semua pilihan group wajib ditutup → produk tidak boleh dipesan
        foreach ($g->variants as $v) {
            $v->update(['is_available' => false]);
        }
        $this->order([$this->opt('Blackpepper')])->assertStatus(422);

        // Buka semula
        Livewire::actingAs($this->owner)->test(Variants::class)->call('toggleAvailable', $g->id, $cheezy->id);
        $this->assertTrue($cheezy->fresh()->is_available);
        $this->order([$this->opt('Cheezy')])->assertSuccessful();
    }
}
