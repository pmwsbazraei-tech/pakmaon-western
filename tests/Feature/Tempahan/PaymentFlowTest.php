<?php

namespace Tests\Feature\Tempahan;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\ChipGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\Http;

class PaymentFlowTest extends PmwTestCase
{
    private function payAndComplete(Order $order, string $result = 'paid'): Payment
    {
        $res = $this->post(route('order.pay', $this->tokenParams($order)))->assertRedirect();
        $payment = $order->payments()->latest('id')->first();
        $this->assertStringContainsString($payment->provider_ref, $res->headers->get('Location'));
        $this->post(route('payments.fake.complete', $payment->provider_ref), ['result' => $result])->assertRedirect();

        return $payment->fresh();
    }

    public function test_booking_whatsapp_opens_selected_branch_and_stays_pending(): void
    {
        $order = $this->placeOrder($this->bookingPayload(['outlet_id' => $this->outlet('yong-peng')->id]));

        $res = $this->get(route('order.whatsapp', $this->tokenParams($order)));
        $url = $res->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/60100000002?text=', $url);
        $text = rawurldecode(substr($url, strpos($url, '=') + 1));
        $this->assertStringContainsString($order->reference, $text);
        $this->assertStringContainsString('Pak Maon Western Yong Peng', $text);
        $this->assertStringContainsString('1 x Set Talam Dua Muka Spaghetti Chicken (Chicken Grill)', $text);

        $order->refresh();
        $this->assertNotNull($order->whatsapp_opened_at);
        $this->assertSame('pending', $order->booking_status);
        $this->get(route('order.show', $this->tokenParams($order)))->assertSee('Menunggu pengesahan cawangan');
    }

    public function test_order_page_requires_token(): void
    {
        $order = $this->placeOrder($this->bookingPayload());
        $this->get(route('order.show', ['reference' => $order->reference, 't' => 'salah']))->assertNotFound();
    }

    public function test_booking_pay_online_marks_paid_but_not_confirmed_by_default(): void
    {
        $order = $this->placeOrder($this->bookingPayload());
        $this->payAndComplete($order);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('pending', $order->booking_status);
    }

    public function test_auto_confirm_after_verified_payment(): void
    {
        $this->form()->update(['auto_confirm_on_payment' => true]);
        $order = $this->placeOrder($this->bookingPayload());
        $this->payAndComplete($order);

        $this->assertSame('confirmed', $order->fresh()->booking_status);
    }

    public function test_purchase_pickup_pay_online_creates_invoice_and_backend_record(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $this->payAndComplete($order);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('pickup', $order->fulfillment);
        $this->get(route('order.show', $this->tokenParams($order)))->assertSee('Bayaran diterima')->assertSee('RM15.90');
    }

    public function test_whatsapp_then_pay_keeps_one_reference_and_one_order(): void
    {
        $order = $this->placeOrder($this->bookingPayload());
        $this->get(route('order.whatsapp', $this->tokenParams($order)))->assertRedirect();
        $this->payAndComplete($order);

        $this->assertSame(1, Order::count());
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_failed_payment_can_retry_on_same_order(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $failed = $this->payAndComplete($order, 'failed');
        $this->assertSame('failed', $failed->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->get(route('order.show', $this->tokenParams($order)))->assertSee('Cuba Bayar Semula');

        $this->payAndComplete($order->fresh());
        $this->assertSame(2, $order->payments()->count());
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, Order::count());
    }

    public function test_double_click_pay_reuses_open_payment(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $this->post(route('order.pay', $this->tokenParams($order)));
        $this->post(route('order.pay', $this->tokenParams($order)));
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_duplicate_webhook_is_processed_once(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $this->post(route('order.pay', $this->tokenParams($order)));
        $payment = $order->payments()->first();
        $payment->update(['raw' => array_merge($payment->raw, ['fake_status' => 'paid'])]);

        $body = json_encode(['id' => $payment->provider_ref, 'event_type' => 'purchase.paid']);
        $sig = hash_hmac('sha256', $body, config('app.key'));
        $svc = app(PaymentService::class);
        $this->assertSame('paid', $svc->handleNotification($body, $sig, 'webhook'));
        $this->assertSame('duplicate', $svc->handleNotification($body, $sig, 'webhook'));
        $this->assertSame(1, $order->activities()->where('action', 'payment_paid')->count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $this->post(route('order.pay', $this->tokenParams($order)));
        $payment = $order->payments()->first();
        $payment->update(['raw' => array_merge($payment->raw, ['fake_status' => 'paid'])]);

        $this->call('POST', route('payments.chip.webhook'), [], [], [], ['HTTP_X_SIGNATURE' => 'palsu', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['id' => $payment->provider_ref]))->assertStatus(401);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_browser_redirect_alone_does_not_mark_paid(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $this->post(route('order.pay', $this->tokenParams($order)));
        // Pelanggan kembali dengan from=gateway tetapi gateway belum sahkan
        $this->get(route('order.show', $this->tokenParams($order) + ['from' => 'gateway']))->assertSee('Menyemak bayaran');
        $this->assertNotSame('paid', $order->fresh()->payment_status);
    }

    private function rsaOptions(): array
    {
        $opts = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        // XAMPP di Windows memerlukan laluan openssl.cnf untuk menjana kunci
        foreach ([getenv('OPENSSL_CONF'), PHP_BINARY ? dirname(PHP_BINARY).'/extras/ssl/openssl.cnf' : null] as $cnf) {
            if ($cnf && is_file($cnf)) {
                return $opts + ['config' => $cnf];
            }
        }

        return $opts;
    }

    public function test_chip_gateway_verifies_rsa_signature_and_amount(): void
    {
        $key = openssl_pkey_new($this->rsaOptions());
        $public = openssl_pkey_get_details($key)['key'];

        $order = $this->placeOrder($this->purchasePayload());
        $chipId = 'chip-purchase-123';
        $remote = fn (int $total) => [
            'id' => $chipId, 'status' => 'paid', 'reference' => $order->reference,
            'purchase' => ['total' => $total, 'currency' => 'MYR'],
        ];

        Http::fake([
            'gate.chip-in.asia/api/v1/purchases/' => Http::response(['id' => $chipId, 'checkout_url' => 'https://gate.chip-in.asia/p/'.$chipId.'/'], 201),
            'gate.chip-in.asia/api/v1/purchases/'.$chipId.'/' => Http::sequence()->push($remote(1))->push($remote(1590)),
        ]);
        $this->app->instance(PaymentGateway::class, new ChipGateway('brand-uuid', 'secret', $public));

        $this->post(route('order.pay', $this->tokenParams($order)))->assertRedirect('https://gate.chip-in.asia/p/'.$chipId.'/');
        Http::assertSent(fn ($r) => $r->url() === 'https://gate.chip-in.asia/api/v1/purchases/' && $r['purchase']['products'][0]['price'] === 1590 && $r['reference'] === $order->reference);

        $sign = function (string $body) use ($key) {
            openssl_sign($body, $s, $key, OPENSSL_ALGO_SHA256);

            return base64_encode($s);
        };
        $svc = app(PaymentService::class);

        // Amaun dari gateway tidak sepadan -> tidak ditanda dibayar
        $body1 = json_encode(['id' => $chipId, 'event_type' => 'purchase.paid', 'n' => 1]);
        $this->assertSame('mismatch', $svc->handleNotification($body1, $sign($body1), 'webhook'));
        $this->assertNotSame('paid', $order->fresh()->payment_status);

        // Tandatangan sah dan amaun sepadan
        $body2 = json_encode(['id' => $chipId, 'event_type' => 'purchase.paid', 'n' => 2]);
        $this->assertSame('paid', $svc->handleNotification($body2, $sign($body2), 'webhook'));
        $this->assertSame('paid', $order->fresh()->payment_status);

        // Tandatangan ditandatangani kunci lain ditolak
        $other = openssl_pkey_new($this->rsaOptions());
        openssl_sign($body2.'x', $bad, $other, OPENSSL_ALGO_SHA256);
        $this->assertSame('invalid_signature', $svc->handleNotification($body2.'x', base64_encode($bad), 'webhook'));
        $this->assertSame(3, PaymentEvent::count());
    }
}
