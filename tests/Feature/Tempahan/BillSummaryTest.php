<?php

namespace Tests\Feature\Tempahan;

use App\Services\PricingService;
use App\Services\WhatsAppService;

class BillSummaryTest extends PmwTestCase
{
    public function test_rounding_follows_malaysian_5_sen_rule(): void
    {
        $form = $this->form();
        $form->update(['prices_include_sst' => false, 'sst_rate' => 0, 'min_order_cents' => null, 'max_order_cents' => null]);
        $toast = $this->toast();

        foreach ([1001 => 1000, 1002 => 1000, 1003 => 1005, 1004 => 1005, 1006 => 1005, 1007 => 1005, 1008 => 1010, 1009 => 1010, 1005 => 1005] as $price => $expected) {
            $toast->update(['price_cents' => $price, 'sale_price_cents' => null]);
            $q = app(PricingService::class)->quote($form->fresh(), [['product_id' => $toast->id, 'quantity' => 1]], null, 'pickup');
            $this->assertSame($expected, $q['total_cents'], "harga {$price}");
            $this->assertSame($expected - $price, $q['rounding_cents']);
        }
    }

    public function test_whatsapp_bill_shows_sst_rounding_then_final_total(): void
    {
        $this->form()->update(['prices_include_sst' => false, 'sst_rate' => 6, 'whatsapp_template' => "Menu:\n{items}\n\n{bill}"]);
        $this->toast()->update(['price_cents' => 1010, 'sale_price_cents' => null]);

        $order = $this->placeOrder($this->purchasePayload(['items' => [['product_id' => $this->toast()->id, 'variant_id' => null, 'quantity' => 1]]]));

        // 10.10 + SST 6% 0.61 = 10.71 -> 10.70
        $this->assertSame(61, $order->sst_added_cents);
        $this->assertSame(-1, $order->rounding_cents);
        $this->assertSame(1070, $order->total_cents);

        $msg = app(WhatsAppService::class)->message($order->fresh());
        $this->assertStringContainsString("Subtotal: RM10.10\nSST 6%: RM0.61\nRounding: -RM0.01\nJumlah akhir: RM10.70", $msg);
        $this->get($order->publicUrl())->assertOk()->assertSeeInOrder(['SST', 'Rounding', '-RM0.01', 'RM10.70']);
    }

    public function test_bill_hides_rounding_when_none(): void
    {
        $this->form()->update(['prices_include_sst' => true, 'sst_rate' => 6, 'whatsapp_template' => '{bill}']);
        $this->toast()->update(['price_cents' => 1060, 'sale_price_cents' => null]);

        $order = $this->placeOrder($this->purchasePayload(['items' => [['product_id' => $this->toast()->id, 'variant_id' => null, 'quantity' => 1]]]));
        $msg = app(WhatsAppService::class)->message($order->fresh());

        $this->assertSame("Subtotal: RM10.60\nSST 6% (termasuk dalam harga): RM0.60\nJumlah akhir: RM10.60", $msg);
    }
}
