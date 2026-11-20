<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use App\Services\WhatsAppService;
use App\Support\OnlinePayment;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, string $reference)
    {
        $order = $this->find($request, $reference);

        // Pelanggan kembali dari gateway: semak status terus dengan gateway (bukan percaya redirect).
        // Gateway hanya dimuatkan bila perlu, supaya halaman ini tidak gagal jika CHIP belum dikonfigurasi.
        if (OnlinePayment::enabled() && $order->payments()->where('status', 'created')->exists()) {
            app(PaymentService::class)->refresh($order);
            $order->refresh();
        }

        $order->load('items', 'outlet', 'form', 'payments');

        return view('shop.order', [
            'order' => $order,
            'whatsappAvailable' => $order->form->action_whatsapp && $order->outlet?->whatsapp,
            'onlineAvailable' => $order->form->onlinePaymentEnabled() && $order->total_cents > 0 && ! $order->isPaid() && ! $order->isCancelled(),
            'fromGateway' => $request->query('from') === 'gateway',
            'lastPayment' => $order->payments->first(),
        ]);
    }

    /** Rekod bahawa pautan WhatsApp dibuka, kemudian buka WhatsApp cawangan. Tidak mendakwa mesej dihantar. */
    public function whatsapp(Request $request, string $reference, WhatsAppService $wa)
    {
        $order = $this->find($request, $reference);
        abort_unless($order->form->action_whatsapp, 404);
        $url = $wa->url($order);
        abort_unless($url, 404);

        if (! $order->whatsapp_opened_at) {
            $order->update(['whatsapp_opened_at' => now()]);
            $order->log('whatsapp_opened', 'Pelanggan membuka pautan WhatsApp ke '.$order->outlet->name.'. Mesej belum tentu dihantar.');
        }

        return redirect()->away($url);
    }

    public function pay(Request $request, string $reference)
    {
        $order = $this->find($request, $reference);
        abort_unless($order->form->onlinePaymentEnabled(), 404);

        try {
            $url = app(PaymentService::class)->start($order);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gateway pembayaran tidak dapat dihubungi. Sila cuba lagi sebentar.');
        }

        return $url ? redirect()->away($url) : redirect($order->publicUrl());
    }

    /** Halaman gateway simulasi (local/testing sahaja). */
    public function fakeGateway(string $id, PaymentGateway $gateway)
    {
        abort_unless($gateway->name() === 'fake', 404);
        $payment = Payment::with('order')->where('provider_ref', $id)->firstOrFail();

        return view('shop.fake-gateway', ['payment' => $payment]);
    }

    public function fakeGatewayComplete(Request $request, string $id, PaymentGateway $gateway, PaymentService $payments)
    {
        abort_unless($gateway->name() === 'fake', 404);
        $payment = Payment::with('order')->where('provider_ref', $id)->firstOrFail();
        $result = $request->validate(['result' => ['required', 'in:paid,failed,cancelled']])['result'];

        if ($payment->status === 'created') {
            $payment->update(['raw' => array_merge($payment->raw ?? [], ['fake_status' => $result])]);
            // Simulasi webhook bertandatangan dari gateway, termasuk penghantaran berganda
            $body = json_encode(['id' => $payment->provider_ref, 'event_type' => 'purchase.'.$result]);
            $sig = hash_hmac('sha256', $body, (string) config('app.key'));
            $payments->handleNotification($body, $sig, 'webhook');
        }

        return redirect()->route('order.show', ['reference' => $payment->order->reference, 't' => $payment->order->public_token, 'from' => 'gateway']);
    }

    private function find(Request $request, string $reference): Order
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        abort_unless(hash_equals($order->public_token, (string) $request->query('t')), 404);

        return $order;
    }
}
