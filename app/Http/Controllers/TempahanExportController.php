<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Phone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Eksport CSV pesanan & booking ikut penapis aktif. Skop outlet dikuatkuasakan. */
class TempahanExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $query = Order::query()->visibleTo($request->user())->filtered($request->query())
            ->with('outlet', 'items')->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Rujukan', 'Jenis', 'Dicipta', 'Outlet', 'Nama', 'Telefon', 'Tarikh booking', 'Tetamu', 'Cara', 'Item', 'Subtotal', 'Diskaun', 'Penghantaran', 'Jumlah', 'Status booking', 'Status bayaran', 'Status fulfillment']);
            $query->chunk(200, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    $m = fn ($c) => number_format($c / 100, 2, '.', '');
                    fputcsv($out, [
                        $o->reference, $o->type, $o->created_at->format('Y-m-d H:i'), $o->outlet?->name, $o->customer_name,
                        Phone::display($o->customer_phone), $o->booking_at?->format('Y-m-d H:i'), $o->guests, $o->fulfillment,
                        $o->items->map(fn ($i) => $i->quantity.'x '.$i->product_name.($i->variant_name ? " ({$i->variant_name})" : ''))->implode('; '),
                        $m($o->subtotal_cents), $m($o->discount_cents), $m($o->delivery_fee_cents), $m($o->total_cents),
                        $o->booking_status, $o->payment_status, $o->fulfillment_status,
                    ]);
                }
            });
            fclose($out);
        }, 'tempahan-pmw-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
