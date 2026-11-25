<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\Products;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

class ProductOrderingTest extends PmwTestCase
{
    private function catalogOrder(): array
    {
        $html = $this->get('/p/pak-maon-western')->assertOk()->getContent();
        $json = str_replace(chr(92).'u0022', '"', $html); // kod unicode petikan dalam x-data
        preg_match_all('/"name":"([^"]*)","description"/', $json, $m);

        return $m[1];
    }

    public function test_owner_can_reorder_categories_and_products_and_customer_page_follows(): void
    {
        $roti = ProductCategory::where('name', 'Roti & Toast')->first();
        $extra = Product::create(['user_id' => $this->owner->id, 'product_category_id' => $roti->id, 'name' => 'Roti Bakar Biasa', 'price_cents' => 300, 'sort' => 2]);
        $this->form()->products()->attach($extra->id, ['sort' => 3]);

        $this->assertSame(['Double Butter Kaya Toast', 'Roti Bakar Biasa', 'Set Talam Dua Muka Spaghetti Chicken'], $this->catalogOrder());

        $set = ProductCategory::where('name', '!=', 'Roti & Toast')->where('user_id', $this->owner->id)->first();

        $c = Livewire::actingAs($this->owner)->test(Products::class);
        $c->call('sortProducts', $roti->id, [$extra->id, $this->toast()->id]);   // Roti Bakar Biasa diseret ke atas
        $c->call('sortCategories', [$set->id, $roti->id]);                        // Roti & Toast diseret ke bawah
        $c->assertOk()->assertSeeInOrder(['Set Talam', 'Roti &amp; Toast', 'Roti Bakar Biasa', 'Double Butter Kaya Toast'], false);

        $this->assertSame(['Set Talam Dua Muka Spaghetti Chicken', 'Roti Bakar Biasa', 'Double Butter Kaya Toast'], $this->catalogOrder());
    }

    public function test_sorting_ignores_other_owners_products_and_other_categories(): void
    {
        $other = User::factory()->create();
        $foreign = Product::create(['user_id' => $other->id, 'name' => 'Lain', 'price_cents' => 100, 'sort' => 50]);
        $roti = ProductCategory::where('name', 'Roti & Toast')->first();

        Livewire::actingAs($this->owner)->test(Products::class)
            ->call('sortProducts', $roti->id, [$foreign->id, $this->talam()->id, $this->toast()->id])->assertOk();

        $this->assertSame(50, $foreign->fresh()->sort);              // produk orang lain tidak disentuh
        $this->assertNotSame($roti->id, $this->talam()->fresh()->product_category_id); // tidak berpindah kategori
        $this->assertSame(1, $this->toast()->fresh()->sort);
    }

    public function test_products_page_has_drag_handles_and_fold_controls(): void
    {
        $this->actingAs($this->owner)->get(route('tempahan.products'))->assertOk()
            ->assertSee('drag-handle', false)->assertSee('Tutup semua')->assertDontSee('▲');
    }

    public function test_owner_can_rename_category_with_pencil_button(): void
    {
        $roti = ProductCategory::where('name', 'Roti & Toast')->first();
        $set = ProductCategory::where('user_id', $this->owner->id)->whereKeyNot($roti->id)->first();

        $this->actingAs($this->owner)->get(route('tempahan.products'))->assertOk()->assertSee('cat-edit', false);

        Livewire::actingAs($this->owner)->test(Products::class)
            ->call('renameCategory', $roti->id, '  Roti Bakar  ')->assertSee('Roti Bakar')
            ->call('renameCategory', $roti->id, '')                 // kosong: diabaikan
            ->call('renameCategory', $roti->id, $set->name);        // nama sedia ada: ditolak

        $this->assertSame('Roti Bakar', $roti->fresh()->name);
        $this->assertStringContainsString('Roti Bakar', $this->get('/p/pak-maon-western')->getContent());

        // Kategori pemilik lain tidak boleh ditukar
        $other = ProductCategory::create(['user_id' => User::factory()->create()->id, 'name' => 'Lain']);
        try {
            Livewire::actingAs($this->owner)->test(Products::class)->call('renameCategory', $other->id, 'Curi');
            $this->fail('Kategori pemilik lain sepatutnya tidak dijumpai.');
        } catch (ModelNotFoundException) {
        }
        $this->assertSame('Lain', $other->fresh()->name);
    }
}
