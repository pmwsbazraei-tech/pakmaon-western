<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\ProductEditor;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class ProductImageUploadTest extends PmwTestCase
{
    public function test_uploaded_image_is_saved_served_and_shown_on_customer_page(): void
    {
        Storage::fake('public');
        $product = Product::where('name', 'Double Butter Kaya Toast')->firstOrFail();

        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $product])
            ->set('photo', UploadedFile::fake()->image('roti.jpg', 400, 400))
            ->assertHasNoErrors()
            ->call('save')->assertHasNoErrors();

        $url = $product->fresh()->image_url;
        $this->assertStringStartsWith(Product::UPLOAD_PREFIX, $url);
        Storage::disk('public')->assertExists('products/'.basename($url));

        $this->get($url)->assertOk();
        $this->assertStringContainsString(basename($url), $this->get('/p/pak-maon-western')->assertOk()->getContent());

        // Ganti gambar: fail lama dipadam
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $product->fresh()])
            ->set('photo', UploadedFile::fake()->image('baru.png'))
            ->call('save')->assertHasNoErrors();
        Storage::disk('public')->assertMissing('products/'.basename($url));

        // Buang gambar
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $product->fresh()])
            ->call('removePhoto')->call('save')->assertHasNoErrors();
        $this->assertNull($product->fresh()->image_url);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $product = Product::where('name', 'Double Butter Kaya Toast')->firstOrFail();

        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $product])
            ->set('photo', UploadedFile::fake()->create('besar.jpg', 9 * 1024, 'image/jpeg'))
            ->assertHasNoErrors('photo')
            ->set('photo', UploadedFile::fake()->create('terlalu-besar.jpg', 11 * 1024, 'image/jpeg'))
            ->assertHasErrors('photo')
            ->set('photo', UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'))
            ->assertHasErrors('photo');

        $this->get('/media/products/../../.env')->assertNotFound();
    }
}
