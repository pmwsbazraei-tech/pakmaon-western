<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\FormEditor;
use App\Models\OrderForm;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

class DuplicateFormTest extends PmwTestCase
{
    public function test_duplicate_copies_settings_with_blank_title_and_slug(): void
    {
        $source = $this->form();
        $source->update(['simple_form' => true, 'sst_rate' => 8, 'whatsapp_template' => 'Templat khas {name}', 'notices' => ['Notis A', 'Notis B']]);
        $outletIds = $source->outlets()->pluck('outlets.id')->sort()->values()->all();
        $productIds = $source->products()->pluck('products.id')->all();

        $c = Livewire::actingAs($this->owner)->withQueryParams(['salin' => $source->id])->test(FormEditor::class)
            ->assertSet('f.title', '')->assertSet('f.slug', '')->assertSet('f.status', 'draft')
            ->assertSet('f.simple_form', true)->assertSet('f.sst_rate', '8.00')
            ->assertSet('f.whatsapp_template', 'Templat khas {name}')
            ->assertSee('Salinan daripada');

        // Tajuk wajib sebelum simpan
        $c->call('save')->assertHasErrors('f.title');

        $c->set('f.title', 'Cawangan Baru')->call('save')->assertHasNoErrors();

        $copy = OrderForm::where('slug', 'cawangan-baru')->firstOrFail();
        $this->assertNotSame($source->id, $copy->id);
        $this->assertSame('draft', $copy->status);
        $this->assertTrue($copy->simple_form);
        $this->assertSame(['Notis A', 'Notis B'], $copy->notices);
        $this->assertSame($outletIds, $copy->outlets()->pluck('outlets.id')->sort()->values()->all());
        $this->assertSame($productIds, $copy->products()->pluck('products.id')->all());

        // Asal tidak berubah
        $this->assertSame('pak-maon-western', $source->fresh()->slug);
    }

    public function test_cannot_duplicate_another_owners_page(): void
    {
        $foreign = OrderForm::create(['user_id' => User::factory()->create()->id, 'title' => 'Asing', 'slug' => 'asing', 'status' => 'draft']);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->owner)->withQueryParams(['salin' => $foreign->id])->test(FormEditor::class);
    }

    public function test_list_has_duplicate_button(): void
    {
        $this->actingAs($this->owner)->get(route('tempahan.forms'))->assertOk()
            ->assertSee(route('tempahan.forms.create', ['salin' => $this->form()->id]), false);
    }
}
