<?php

namespace Tests\Feature\Tempahan;

use App\Models\Order;

class CheckoutTest extends PmwTestCase
{
    public function test_public_page_opens_without_login_and_shows_catalog(): void
    {
        $this->get(route('form.show', 'pak-maon-western'))
            ->assertOk()
            ->assertSee('Double Butter Kaya Toast')
            ->assertSee('Set Talam Dua Muka Spaghetti Chicken')
            ->assertSee('Chicken Crispy Breast');
    }

    public function test_sst_inclusive_price_is_not_charged_twice(): void
    {
        $res = $this->postJson(route('form.quote', 'pak-maon-western'), [
            'items' => [['product_id' => $this->toast()->id, 'quantity' => 1]],
            'outlet_id' => $this->outlet()->id,
            'fulfillment' => 'dine_in',
        ])->assertOk();

        $this->assertSame('RM5.30', $res->json('total'));
        $this->assertSame(530, $res->json('total_cents'));
        $this->assertNull($res->json('sst_added'));

        $order = $this->placeOrder($this->purchasePayload(['items' => [['product_id' => $this->toast()->id, 'quantity' => 1]]]));
        $this->assertSame(530, $order->total_cents);
        $this->assertSame(0, $order->sst_added_cents);
        $this->assertSame(30, $order->sst_included_cents); // 530 * 6/106
    }

    public function test_variant_prices_come_from_server_not_client(): void
    {
        $variant = $this->talam()->variants()->where('name', 'Crispy Chicken Thigh')->first();
        $order = $this->placeOrder($this->bookingPayload([
            'items' => [['product_id' => $this->talam()->id, 'variant_id' => $variant->id, 'quantity' => 2, 'price' => 1]],
        ]));
        $this->assertSame(12940, $order->total_cents);
        $this->assertSame('Crispy Chicken Thigh', $order->items->first()->variant_name);
    }

    public function test_phone_is_normalized_and_required(): void
    {
        $order = $this->placeOrder($this->bookingPayload(['phone' => '+60 12-345 6789']));
        $this->assertSame('60123456789', $order->customer_phone);

        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['phone' => '12345']))
            ->assertStatus(422)->assertJsonValidationErrors('phone_normalized');
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['name' => '']))
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_double_submit_with_same_key_creates_one_order(): void
    {
        $payload = $this->bookingPayload();
        $a = $this->postJson(route('form.store', 'pak-maon-western'), $payload)->assertOk()->json('reference');
        $b = $this->postJson(route('form.store', 'pak-maon-western'), $payload)->assertOk()->json('reference');

        $this->assertSame($a, $b);
        $this->assertSame(1, Order::count());
    }

    public function test_branch_is_required_for_booking(): void
    {
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['outlet_id' => null]))
            ->assertStatus(422)->assertJsonValidationErrors('outlet_id');
    }

    public function test_preorder_required_rejects_empty_cart_but_table_only_allows_it(): void
    {
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['items' => []]))
            ->assertStatus(422)->assertJsonValidationErrors('items');

        $order = $this->placeOrder($this->bookingPayload(['items' => []]), 'tempah-meja');
        $this->assertSame(0, $order->total_cents);
        $this->assertSame('pending', $order->booking_status);
    }

    public function test_purchase_requires_items(): void
    {
        $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload(['items' => []]))
            ->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_purpose_not_enabled_on_form_is_rejected(): void
    {
        $this->postJson(route('form.store', 'tempah-meja'), $this->purchasePayload())
            ->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_delivery_needs_address_only_when_enabled(): void
    {
        $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload(['fulfillment' => 'delivery']))
            ->assertStatus(422)->assertJsonValidationErrors('fulfillment');

        $this->form()->update(['allow_delivery' => true, 'delivery_fee_cents' => 500]);
        $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload(['fulfillment' => 'delivery']))
            ->assertStatus(422)->assertJsonValidationErrors('delivery_address');

        $order = $this->placeOrder($this->purchasePayload(['fulfillment' => 'delivery', 'delivery_address' => 'No 1, Jalan Test, Melaka']));
        $this->assertSame(1590 + 500, $order->total_cents);
    }

    public function test_draft_form_is_hidden_and_maintenance_is_closed(): void
    {
        $this->form()->update(['status' => 'draft']);
        $this->get(route('form.show', 'pak-maon-western'))->assertNotFound();

        $this->form()->update(['status' => 'maintenance']);
        $this->get(route('form.show', 'pak-maon-western'))->assertStatus(503);
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload())->assertStatus(503);
    }
}
