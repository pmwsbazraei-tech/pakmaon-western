<?php

namespace Tests\Feature\Tempahan;

class CustomerLanguageTest extends PmwTestCase
{
    public function test_customer_page_defaults_to_malay_and_can_switch_to_english(): void
    {
        // Lalai: Bahasa Melayu
        $this->get('/p/pak-maon-western')->assertOk()
            ->assertSee('<html lang="ms">', false)->assertSee('Beli untuk Pickup')
            ->assertSee(route('locale.switch', 'en'), false);

        // Pelanggan tukar ke English
        $this->from('/p/pak-maon-western')->get(route('locale.switch', 'en'))->assertRedirect('/p/pak-maon-western');
        $this->get('/p/pak-maon-western')->assertOk()
            ->assertSee('<html lang="en">', false)->assertDontSee('Beli untuk Pickup');

        // Mesej ralat pelayan juga ikut bahasa
        $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload(['order_no' => '']))
            ->assertStatus(422)->assertJsonFragment(['order_no' => ['Please enter your order number.']]);

        // Tukar semula ke BM
        $this->get(route('locale.switch', 'ms'));
        $this->get('/p/pak-maon-western')->assertSee('Beli untuk Pickup');
    }
}
