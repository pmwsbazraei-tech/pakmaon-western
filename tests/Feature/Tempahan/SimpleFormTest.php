<?php

namespace Tests\Feature\Tempahan;

use App\Services\WhatsAppService;
use Illuminate\Support\Str;

class SimpleFormTest extends PmwTestCase
{
    public function test_simple_form_accepts_only_core_details_and_records_pickup(): void
    {
        // Page booking-sahaja pun boleh guna borang ringkas
        $this->form('tempah-meja')->update(['simple_form' => true, 'whatsapp_template' => null]);

        $this->get('/p/tempah-meja')->assertOk()->assertSee('sf-outlet-s');

        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'name' => 'Siti Test',
            'phone' => '012-345 6789',
            'order_no' => 'SMB-01',
            'outlet_id' => $this->outlet()->id,
            'email' => 'siti@example.test',
            'items' => [['product_id' => $this->toast()->id, 'variant_id' => null, 'quantity' => 2]],
        ];
        $order = $this->placeOrder($payload, 'tempah-meja');

        $this->assertSame('purchase', $order->type);
        $this->assertSame('pickup', $order->fulfillment);
        $this->assertNull($order->booking_at);
        $this->assertSame('SMB-01', $order->customer_order_no);

        $msg = app(WhatsAppService::class)->message($order);
        foreach (['Nama: Siti Test', 'Nombor order: SMB-01', 'Cawangan: '.$this->outlet()->name, 'Emel: siti@example.test', 'Telefon: '] as $line) {
            $this->assertStringContainsString($line, $msg);
        }
        $this->assertStringNotContainsString('Tarikh:', $msg);
    }

    public function test_simple_form_still_requires_core_fields(): void
    {
        $this->form()->update(['simple_form' => true]);

        $this->postJson(route('form.store', 'pak-maon-western'), ['idempotency_key' => (string) Str::uuid(), 'items' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'order_no', 'outlet_id'])
            ->assertJsonMissingValidationErrors(['booking_date', 'booking_time', 'guests', 'fulfillment']);
    }
}
