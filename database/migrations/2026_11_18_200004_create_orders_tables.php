<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('public_token', 48)->unique();
            $table->string('idempotency_key', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // pemilik
            $table->foreignId('order_form_id')->constrained()->restrictOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();   // rekod kekal jika outlet dipadam
            $table->string('type');                      // booking | purchase

            $table->string('customer_name');
            $table->string('customer_phone', 20);        // 60XXXXXXXXX
            $table->string('customer_email')->nullable();

            // Booking
            $table->dateTime('booking_at')->nullable()->index();
            $table->unsignedSmallInteger('guests')->nullable();

            // Fulfillment
            $table->string('fulfillment');               // dine_in | pickup | delivery
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();

            // Wang (sen, MYR)
            $table->boolean('prices_include_sst')->default(true);
            $table->decimal('sst_rate', 5, 2)->default(6.00);
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('delivery_fee_cents')->default(0);
            $table->unsignedInteger('sst_added_cents')->default(0);     // hanya jika harga TIDAK termasuk SST
            $table->unsignedInteger('sst_included_cents')->default(0);  // bahagian SST dalam harga (maklumat)
            $table->unsignedInteger('total_cents');
            $table->string('currency', 3)->default('MYR');

            // Status berasingan
            $table->string('booking_status')->nullable();      // pending | confirmed | cancelled | completed | no_show
            $table->string('payment_status')->default('unpaid'); // unpaid | pending | paid | failed | expired | refunded
            $table->string('fulfillment_status')->default('new'); // new | preparing | ready | completed | cancelled

            $table->timestamp('whatsapp_opened_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->boolean('stock_released')->default(false);
            $table->timestamps();

            $table->index(['outlet_id', 'booking_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Salinan sejarah: tidak berubah walaupun katalog berubah
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total_cents');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider');                       // chip | fake
            $table->string('provider_ref')->unique();         // ID purchase gateway
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('MYR');
            $table->string('status')->default('created');     // created | paid | failed | cancelled | expired
            $table->text('checkout_url')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('dedupe_key')->unique();           // hash badan permintaan: webhook berulang diabaikan
            $table->string('provider_ref')->nullable()->index();
            $table->string('event_type')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('outcome')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('order_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_activities');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
