<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog jualan online, dimiliki pemilik (user) dan dikongsi semua outletnya.
 * Berasingan daripada `menus` (food cost), boleh dipautkan melalui menu_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('price_cents')->nullable();        // null jika harga ikut variasi
            $table->unsignedInteger('promo_price_cents')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->integer('stock')->nullable();                      // null = tiada had
            $table->unsignedSmallInteger('max_per_order')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'sku']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('promo_price_cents')->nullable();
            $table->integer('stock')->nullable();
            $table->unsignedSmallInteger('max_per_order')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Ketersediaan produk mengikut outlet. Tiada baris = tersedia.
        Schema::create('outlet_product', function (Blueprint $table) {
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available')->default(true);
            $table->primary(['outlet_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('change');
            $table->string('reason');   // order | order_release | adjustment
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('outlet_product');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
