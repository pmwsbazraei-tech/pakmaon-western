<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\FormEditor;
use App\Models\ProductCategory;
use Livewire\Livewire;

class FormVariantSelectionTest extends PmwTestCase
{
    public function test_landing_page_lists_variants_for_reference_only(): void
    {
        $grill = $this->talam()->activeVariants()->where('name', 'Chicken Grill')->firstOrFail();
        $grill->variant->update(['is_available' => false]);

        // Editor: tiada tick variant; variant dipapar sebagai rujukan dengan status Tidak tersedia
        Livewire::actingAs($this->owner)->test(FormEditor::class, ['form' => $this->form()])
            ->assertDontSee('shownVariantIds')->assertSee('Chicken Grill')->assertSee('Tidak tersedia')
            ->assertSee('Buka semua variant')
            ->call('save')->assertHasNoErrors();

        // Halaman pelanggan: semua pilihan dipapar; yang ditutup di halaman Variant ditanda tidak tersedia
        $html = $this->get('/p/pak-maon-western')->assertOk()->getContent();
        $this->assertStringContainsString('Chicken Grill', $html);
        $this->assertStringContainsString('Crispy Chicken Thigh', $html);
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
