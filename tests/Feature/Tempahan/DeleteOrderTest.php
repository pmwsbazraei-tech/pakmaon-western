<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\OrderDetail;
use App\Livewire\Tempahan\Orders;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

class DeleteOrderTest extends PmwTestCase
{
    public function test_owner_bulk_deletes_orders_and_stock_is_returned(): void
    {
        $this->toast()->update(['stock' => 10]);
        $a = $this->placeOrder($this->purchasePayload());
        $b = $this->placeOrder($this->purchasePayload());
        $keep = $this->placeOrder($this->purchasePayload());
        $this->assertSame(1, $this->toast()->fresh()->stock); // 3 x 3 dikurangkan

        Livewire::actingAs($this->owner)->test(Orders::class)
            ->set('selected', [(string) $a->id, (string) $b->id])
            ->assertSee('2 dipilih')
            ->call('deleteSelected')
            ->assertDispatched('toast')
            ->assertSet('selected', []);

        $this->assertModelMissing($a);
        $this->assertModelMissing($b);
        $this->assertModelExists($keep);
        $this->assertDatabaseMissing('order_items', ['order_id' => $a->id]);
        $this->assertSame(7, $this->toast()->fresh()->stock);
    }

    public function test_owner_deletes_from_detail_page(): void
    {
        $order = $this->placeOrder($this->purchasePayload());

        Livewire::actingAs($this->owner)->test(OrderDetail::class, ['order' => $order])
            ->assertSee('Padam pesanan')
            ->call('deleteOrder')
            ->assertRedirect(route('tempahan.orders'));

        $this->assertModelMissing($order);
    }

    public function test_paid_online_order_cannot_be_deleted(): void
    {
        $order = $this->placeOrder($this->purchasePayload());
        $order->payments()->create(['provider' => 'fake', 'provider_ref' => 'x1', 'amount_cents' => $order->total_cents, 'status' => 'paid']);

        Livewire::actingAs($this->owner)->test(Orders::class)
            ->set('selected', [(string) $order->id])->call('deleteSelected');
        Livewire::actingAs($this->owner)->test(OrderDetail::class, ['order' => $order])->call('deleteOrder');

        $this->assertModelExists($order);
    }

    public function test_staff_and_other_owners_cannot_delete(): void
    {
        $order = $this->placeOrder($this->bookingPayload());

        Livewire::actingAs($this->staff())->test(Orders::class)
            ->assertDontSee('Pilih semua')
            ->set('selected', [(string) $order->id])->call('deleteSelected')->assertForbidden();

        $other = User::factory()->create();
        Livewire::actingAs($other)->test(Orders::class)
            ->set('selected', [(string) $order->id])->call('deleteSelected');

        $this->assertSame(1, Order::whereKey($order->id)->count());
    }
}
