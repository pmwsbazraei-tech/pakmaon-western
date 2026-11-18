<?php

namespace Tests\Feature\Tempahan;

use App\Services\OrderService;

class BookingRulesTest extends PmwTestCase
{
    public function test_slot_full_is_rejected(): void
    {
        $this->outlet()->update(['slot_capacity' => 5]);
        $this->placeOrder($this->bookingPayload(['guests' => 4]));

        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['guests' => 2]))
            ->assertStatus(422)->assertJsonValidationErrors('booking_time');

        $slots = $this->getJson(route('form.slots', ['slug' => 'pak-maon-western', 'outlet_id' => $this->outlet()->id, 'date' => '2026-11-19']))->json('slots');
        $this->assertSame(1, collect($slots)->firstWhere('time', '19:00')['remaining']);
    }

    public function test_closed_day_and_closure_date_are_rejected(): void
    {
        // 24 Nov 2026 ialah hari Selasa: Tangkak tutup
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['booking_date' => '2026-11-24']))
            ->assertStatus(422)->assertJsonValidationErrors('booking_time');

        $this->outlet()->closures()->create(['date' => '2026-11-19', 'reason' => 'Cuti']);
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload())
            ->assertStatus(422)->assertJsonPath('errors.booking_time.0', 'Cawangan tutup pada tarikh ini.');
    }

    public function test_slot_outside_hours_or_too_soon_is_rejected(): void
    {
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['booking_time' => '23:00']))
            ->assertStatus(422);
        // Kini 10:00, notis minimum 60 minit, kedai buka 11:00: 10:30 tidak sah, 11:00 sah
        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['booking_date' => '2026-11-18', 'booking_time' => '10:30']))
            ->assertStatus(422);
        $this->placeOrder($this->bookingPayload(['booking_date' => '2026-11-18', 'booking_time' => '11:00']));
    }

    public function test_branch_change_flags_unavailable_items(): void
    {
        \DB::table('outlet_product')->insert(['outlet_id' => $this->outlet('yong-peng')->id, 'product_id' => $this->talam()->id, 'is_available' => false]);

        $quote = $this->postJson(route('form.quote', 'pak-maon-western'), [
            'items' => $this->bookingPayload()['items'], 'outlet_id' => $this->outlet('yong-peng')->id, 'fulfillment' => 'dine_in',
        ])->json();
        $this->assertSame(['Set Talam Dua Muka Spaghetti Chicken (Chicken Grill)'], $quote['unavailable']);

        $this->postJson(route('form.store', 'pak-maon-western'), $this->bookingPayload(['outlet_id' => $this->outlet('yong-peng')->id]))
            ->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_last_stock_cannot_be_sold_twice_and_cancel_releases_it(): void
    {
        $this->toast()->update(['stock' => 1]);
        $items = [['product_id' => $this->toast()->id, 'quantity' => 1]];

        $first = $this->placeOrder($this->purchasePayload(['items' => $items]));
        $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload(['items' => $items]))
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->assertSame(0, $this->toast()->fresh()->stock);

        app(OrderService::class)->releaseStock($first);
        app(OrderService::class)->releaseStock($first); // kali kedua tiada kesan
        $this->assertSame(1, $this->toast()->fresh()->stock);
    }

    public function test_purchase_limit_per_order(): void
    {
        $this->toast()->update(['max_per_order' => 2]);
        $this->postJson(route('form.store', 'pak-maon-western'), $this->purchasePayload())
            ->assertStatus(422)->assertJsonValidationErrors('items');
    }
}
