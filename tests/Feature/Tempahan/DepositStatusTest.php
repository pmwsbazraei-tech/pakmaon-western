<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\OrderDetail;
use App\Livewire\Tempahan\Orders;
use Livewire\Livewire;

class DepositStatusTest extends PmwTestCase
{
    public function test_deposit_then_fully_paid_shows_balance(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $total = $order->total_cents;

        $c = Livewire::actingAs($this->owner)->test(OrderDetail::class, ['order' => $order])
            ->assertSee('Deposit')->assertSee('Fully Paid')->assertDontSee('Disediakan');

        $c->set('depositAmount', '5.00')->call('recordDeposit')->assertHasNoErrors();

        $order->refresh();
        $this->assertSame('deposit', $order->fulfillment_status);
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame(500, $order->deposit_cents);
        $this->assertSame($total - 500, $order->balanceCents());
        $c->assertSee('Deposit diterima')->assertSee('Baki');
        $this->assertDatabaseHas('order_activities', ['order_id' => $order->id, 'action' => 'deposit_recorded']);

        Livewire::actingAs($this->owner)->test(Orders::class)->assertSee('Baki');
        $this->get($order->publicUrl())->assertOk()->assertSee('Baki perlu dibayar');

        $c->call('setStatus', 'fulfillment_status', 'paid');
        $order->refresh();
        $this->assertSame('paid', $order->fulfillment_status);
        $this->assertTrue($order->isPaid());
        $this->assertSame(0, $order->balanceCents());
        $this->assertNotNull($order->paid_at);
    }

    public function test_deposit_must_be_positive_and_less_than_total(): void
    {
        $order = $this->placeOrder($this->purchasePayload());

        $c = Livewire::actingAs($this->owner)->test(OrderDetail::class, ['order' => $order]);
        $c->set('depositAmount', '')->call('recordDeposit')->assertHasErrors('depositAmount');
        $c->set('depositAmount', number_format($order->total_cents / 100, 2, '.', ''))->call('recordDeposit')->assertHasErrors('depositAmount');

        $this->assertSame(0, $order->fresh()->deposit_cents);
        $this->assertSame('new', $order->fresh()->fulfillment_status);
    }

    public function test_back_to_new_clears_manual_deposit_and_cancel_is_final(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $c = Livewire::actingAs($this->owner)->test(OrderDetail::class, ['order' => $order]);

        $c->set('depositAmount', '3')->call('recordDeposit');
        $c->call('setStatus', 'fulfillment_status', 'new');
        $order->refresh();
        $this->assertSame(['new', 'unpaid', 0], [$order->fulfillment_status, $order->payment_status, $order->deposit_cents]);

        $c->call('setStatus', 'fulfillment_status', 'cancelled');
        $c->set('depositAmount', '3')->call('recordDeposit');
        $this->assertSame('cancelled', $order->fresh()->fulfillment_status);
        $this->assertSame(0, $order->fresh()->deposit_cents);
    }
}
