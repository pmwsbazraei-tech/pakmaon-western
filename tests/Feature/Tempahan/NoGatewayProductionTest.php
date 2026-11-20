<?php

namespace Tests\Feature\Tempahan;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * Regresi: di production tanpa kunci CHIP, halaman pesanan dahulu 500
 * kerana PaymentGateway dimuatkan (gateway simulasi ditolak di production).
 */
class NoGatewayProductionTest extends PmwTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.payment_driver' => 'fake']);
        $this->app['env'] = 'production';
        // Dalam env "production", CSRF tidak lagi dilangkau automatik oleh ujian
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_order_page_and_whatsapp_work_without_payment_gateway(): void
    {
        $this->form()->update(['simple_form' => true, 'action_whatsapp' => true, 'action_online' => true]);

        // Halaman borang: Bayar Online tidak ditawarkan
        $this->get('/p/pak-maon-western')->assertOk()->assertSee('actionOnline'.chr(92).'u0022:false', false);

        $order = $this->placeOrder($this->purchasePayload());

        $page = $this->get($order->publicUrl())->assertOk();
        $page->assertDontSee('Bayar Online');
        $page->assertSee('WhatsApp');

        $wa = $this->get(str_replace('?', '/whatsapp?', $order->publicUrl()));
        $wa->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/', $wa->headers->get('Location'));
        $this->assertStringNotContainsString('Pautan%20bayaran', $wa->headers->get('Location'));

        // Laluan bayar ditutup, bukan 500
        $this->post(str_replace('?', '/pay?', $order->publicUrl()))->assertNotFound();
    }
}
