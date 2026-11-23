<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\FormEditor;
use App\Models\ProductCategory;
use Livewire\Livewire;

class FormVariantSelectionTest extends PmwTestCase
{
    public function test_unticked_variant_is_hidden_on_that_landing_page_only(): void
    {
        $form = $this->form();
        $grill = $this->talam()->activeVariants()->where('name', 'Chicken Grill')->firstOrFail();
        $all = $this->talam()->activeVariants()->pluck('id')->map(fn ($i) => (string) $i)->all();

        // Semua variant ditick secara lalai
        $c = Livewire::actingAs($this->owner)->test(FormEditor::class, ['form' => $form]);
        foreach ($all as $id) {
            $this->assertContains($id, $c->get('shownVariantIds'));
        }

        // Untick Chicken Grill
        $c->set('shownVariantIds', array_values(array_diff($c->get('shownVariantIds'), [(string) $grill->id])))
            ->call('save')->assertHasNoErrors();
        $this->assertSame([$grill->id], $form->fresh()->hiddenVariantIds());

        // Halaman pelanggan: Chicken Grill tiada, pilihan lain ada
        $html = $this->get('/p/pak-maon-western')->assertOk()->getContent();
        $this->assertStringNotContainsString('Chicken Grill', $html);
        $this->assertStringContainsString('Crispy Chicken Thigh', $html);

        // Pelayan menolak pesanan untuk variant yang disorok
        $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload([
            'items' => [['product_id' => $this->talam()->id, 'variant_id' => $grill->id, 'quantity' => 1]],
        ]))->assertStatus(422);

        // Landing page lain tidak terjejas
        $this->assertSame([], $this->form('tempah-meja')->hiddenVariantIds());
    }

    public function test_ticked_product_needs_at_least_one_variant(): void
    {
        $form = $this->form();
        $talamVariants = $this->talam()->activeVariants()->pluck('id')->map(fn ($i) => (string) $i)->all();

        $c = Livewire::actingAs($this->owner)->test(FormEditor::class, ['form' => $form]);
        $c->set('shownVariantIds', array_values(array_diff($c->get('shownVariantIds'), $talamVariants)))
            ->call('save')->assertHasErrors('productIds');
    }

    public function test_products_follow_product_and_stock_order(): void
    {
        $roti = ProductCategory::where('name', 'Roti & Toast')->firstOrFail();
        $set = ProductCategory::where('name', 'Set Talam')->firstOrFail();
        $set->update(['sort' => 0]);
        $roti->update(['sort' => 5]);

        Livewire::actingAs($this->owner)->test(FormEditor::class, ['form' => $this->form()])
            ->assertSeeInOrder(['Set Talam', 'Set Talam Dua Muka Spaghetti Chicken', 'Roti &amp; Toast', 'Double Butter Kaya Toast'], false);
    }
}
