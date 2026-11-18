<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    /** success_callback CHIP (ditandatangani dengan kunci awam syarikat). */
    public function callback(Request $request, PaymentService $payments)
    {
        return $this->handle($request, $payments, 'callback');
    }

    /** Webhook CHIP (ditandatangani dengan kunci awam webhook). */
    public function webhook(Request $request, PaymentService $payments)
    {
        return $this->handle($request, $payments, 'webhook');
    }

    private function handle(Request $request, PaymentService $payments, string $kind)
    {
        $outcome = $payments->handleNotification($request->getContent(), $request->header('X-Signature'), $kind);

        return response()->json(['outcome' => $outcome], $outcome === 'invalid_signature' ? 401 : 200);
    }
}
