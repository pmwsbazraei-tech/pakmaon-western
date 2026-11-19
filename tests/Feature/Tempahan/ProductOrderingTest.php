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

        $c = Livewire::actingAs($this->owner)->test(Products::class);
        $c->call('moveProduct', $extra->id, -1);          // Roti Bakar Biasa ke atas dalam kategori
        $c->call('moveCategory', $roti->id, 1);           // Roti & Toast selepas Set Talam
        $c->assertOk()->assertSeeInOrder(['Set Talam', 'Roti &amp; Toast', 'Roti Bakar Biasa', 'Double Butter Kaya Toast'], false);

        $this->assertSame(['Set Talam Dua Muka Spaghetti Chicken', 'Roti Bakar Biasa', 'Double Butter Kaya Toast'], $this->catalogOrder());
    }

    public function test_cannot_move_other_owners_product_and_edges_are_noop(): void
    {
        $other = User::factory()->create();
        $foreign = Product::create(['user_id' => $other->id, 'name' => 'Lain', 'price_cents' => 100]);

        $c = Livewire::actingAs($this->owner)->test(Products::class);
        $c->call('moveProduct', $this->toast()->id, -1)->assertOk(); // sudah paling atas
        $this->expectException(ModelNotFoundException::class);
        $c->call('moveProduct', $foreign->id, 1);
    }
}
