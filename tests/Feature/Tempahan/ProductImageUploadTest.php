<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\ProductEditor;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class ProductImageUploadTest extends PmwTestCase
{
    public function test_rotate_existing_and_new_image_then_save(): void
    {
        Storage::fake('public');
        $product = Product::where('name', 'Double Butter Kaya Toast')->firstOrFail();

        // Muat naik gambar landskap 200x100 + putar 90° terus
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $product])
            ->set('photo', UploadedFile::fake()->image('landskap.jpg', 200, 100))
            ->call('rotate', 1)->assertSet('rotation', 90)
            ->call('save')->assertHasNoErrors()->assertSet('rotation', 0);

        $first = basename($product->fresh()->image_url);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get('products/'.$first));
        $this->assertSame([100, 200], [$w, $h]);
        $this->assertCount(1, Storage::disk('public')->files('products'));

        // Gambar sedia ada: putar ke kiri 90° → kembali landskap, fail lama dipadam, URL baharu
        Livewire::actingAs($this->owner)->test(ProductEditor::class, ['product' => $product->fresh()])
            ->call('rotate', -1)->assertSet('rotation', 270)
            ->call('save')->assertHasNoErrors();

        $second = basename($product->fresh()->image_url);
        $this->assertNotSame($first, $second);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get('products/'.$second));
        $this->assertSame([200, 100], [$w, $h]);
        Storage::disk('public')->assertMissing('products/'.$first);
        $this->assertCount(1, Storage::disk('public')->files('products'));
    }

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
