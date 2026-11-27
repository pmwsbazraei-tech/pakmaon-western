<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\OrderDetail;
use App\Livewire\Tempahan\Orders;
use Livewire\Livewire;

class OrderReferenceTest extends PmwTestCase
{
    public function test_list_shows_customer_order_number_as_reference_and_can_search_it(): void
    {
        $mine = $this->placeOrder($this->purchasePayload(['order_no' => '7788']));
        $other = $this->placeOrder($this->purchasePayload(['order_no' => '1111', 'name' => 'Orang Lain']));

        Livewire::actingAs($this->owner)->test(Orders::class)
            ->assertSee('7788')->assertSee($mine->reference)
            ->set('q', '7788')
            ->assertSee('7788')->assertDontSee('1111');

        Livewire::actingAs($this->owner)->test(OrderDetail::class, ['order' => $mine])
            ->assertSee('7788')->assertSee('Rujukan sistem');

        $this->assertSame('1111', $other->displayReference());
    }

    public function test_falls_back_to_system_reference_when_no_order_number(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $order->update(['customer_order_no' => null]);

        $this->assertSame($order->reference, $order->fresh()->displayReference());
    }
}
