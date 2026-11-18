<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // pemilik
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');          // draft | published | maintenance
            $table->text('description')->nullable();
            $table->string('banner_url')->nullable();
            $table->json('notices')->nullable();                 // makluman yang dipaparkan
            $table->text('welcome_popup')->nullable();
            $table->text('maintenance_message')->nullable();

            // Tujuan
            $table->boolean('purpose_booking')->default(true);
            $table->boolean('purpose_purchase')->default(false);
            $table->string('booking_mode')->default('preorder_required'); // preorder_required | table_only_allowed
            $table->text('booking_notice')->nullable();

            // Harga
            $table->boolean('prices_include_sst')->default(true);
            $table->decimal('sst_rate', 5, 2)->default(6.00);
            $table->unsignedInteger('min_order_cents')->nullable();
            $table->unsignedInteger('max_order_cents')->nullable();

            // Fulfillment pembelian produk
            $table->boolean('allow_pickup')->default(true);
            $table->boolean('allow_delivery')->default(false);
            $table->unsignedInteger('delivery_fee_cents')->default(0);

            // Tindakan selepas Proceed
            $table->boolean('action_whatsapp')->default(true);
            $table->boolean('action_online')->default(true);
            $table->boolean('auto_confirm_on_payment')->default(false);
            $table->text('whatsapp_template')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_form_outlet', function (Blueprint $table) {
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_form_id')->constrained()->cascadeOnDelete();
            $table->primary(['outlet_id', 'order_form_id']);
        });

        Schema::create('order_form_product', function (Blueprint $table) {
            $table->foreignId('order_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->primary(['order_form_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_form_product');
        Schema::dropIfExists('order_form_outlet');
        Schema::dropIfExists('order_forms');
    }
};
