<?php

namespace Tests\Feature\Tempahan;

use App\Models\Order;
use App\Models\OrderForm;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Support\DefaultOutletData;
use Carbon\CarbonImmutable;
use Database\Seeders\TempahanDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class PmwTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        // Rabu, 18 Nov 2026, 10:00 waktu Malaysia
        $this->travelTo(CarbonImmutable::parse('2026-11-18 10:00', 'Asia/Kuala_Lumpur'));
        config(['services.payment_driver' => 'fake']);

        // Pemilik dengan outlet DEFAULT + 3 outlet cawangan tempahan
        $this->owner = User::factory()->create();
        DefaultOutletData::provision($this->owner);
        app(TempahanDemoSeeder::class)->run($this->owner);
    }

    protected function staff(): User
    {
        return User::where('email', 'tangkak@pakmaon.test')->firstOrFail();
    }

    protected function form(string $slug = 'pak-maon-western'): OrderForm
    {
        return OrderForm::where('slug', $slug)->firstOrFail();
    }

    protected function outlet(string $slug = 'tangkak'): Outlet
    {
        return Outlet::where('slug', $slug)->firstOrFail();
    }

    protected function toast(): Product
    {
        return Product::where('sku', 'PMW-TOAST-DBK')->firstOrFail();
    }

    protected function talam(): Product
    {
        return Product::where('sku', 'PMW-TALAM-SPG')->firstOrFail();
    }

    protected function bookingPayload(array $over = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'booking',
            'name' => 'Aminah Test',
            'order_no' => 'PMW-1001',
            'phone' => '012-345 6789',
            'outlet_id' => $this->outlet()->id,
            'booking_date' => '2026-11-19',
            'booking_time' => '19:00',
            'guests' => 4,
            'items' => [
                ['product_id' => $this->toast()->id, 'variant_id' => null, 'quantity' => 2],
                ['product_id' => $this->talam()->id, 'variant_id' => $this->talam()->variants()->where('name', 'Chicken Grill')->value('id'), 'quantity' => 1],
            ],
        ], $over);
    }

    protected function purchasePayload(array $over = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'purchase',
            'name' => 'Ali Test',
            'order_no' => 'PMW-2002',
            'phone' => '+60 19-876 5432',
            'outlet_id' => $this->outlet('melaka')->id,
            'fulfillment' => 'pickup',
            'items' => [['product_id' => $this->toast()->id, 'variant_id' => null, 'quantity' => 3]],
        ], $over);
    }

    protected function placeOrder(array $payload, string $slug = 'pak-maon-western'): Order
    {
        $res = $this->postJson(route('form.store', $slug), $payload)->assertOk();

        return Order::where('reference', $res->json('reference'))->firstOrFail();
    }

    protected function tokenParams(Order $order): array
    {
        return ['reference' => $order->reference, 't' => $order->public_token];
    }
}
