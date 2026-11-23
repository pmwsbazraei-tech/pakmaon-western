<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Variant group dikongsi: satu group (cth. "Chicken") ada beberapa pilihan (Chicken 1, Chicken 2…)
 * dengan harga masing-masing. Produk menanda group; semua pilihan dalam group itu jadi variant produk.
 * product_variants kekal sebagai pautan produk ↔ pilihan (dirujuk oleh pesanan lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('promo_price_cents')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variant_group', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'variant_group_id']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        // Pindah variasi sedia ada: satu group bagi setiap produk (nama group = nama produk)
        $products = DB::table('products')->whereIn('id', DB::table('product_variants')->where('is_active', true)->select('product_id'))
            ->orderBy('user_id')->orderBy('id')->get(['id', 'user_id', 'name']);
        foreach ($products as $i => $p) {
            $groupId = DB::table('variant_groups')->insertGetId([
                'user_id' => $p->user_id, 'name' => $p->name, 'sort' => $i, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('product_variant_group')->insert(['product_id' => $p->id, 'variant_group_id' => $groupId]);

            $rows = DB::table('product_variants')->where('product_id', $p->id)->where('is_active', true)->orderBy('sort')->orderBy('id')->get();
            foreach ($rows as $j => $r) {
                $variantId = DB::table('variants')->insertGetId([
                    'variant_group_id' => $groupId, 'name' => $r->name, 'sku' => $r->sku, 'price_cents' => $r->price_cents,
                    'promo_price_cents' => $r->promo_price_cents, 'sort' => $j, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('product_variants')->where('id', $r->id)
                    ->update(['variant_id' => $variantId, 'sort' => $i * 1000 + $j, 'stock' => null, 'max_per_order' => null]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
        });
        Schema::dropIfExists('product_variant_group');
        Schema::dropIfExists('variants');
        Schema::dropIfExists('variant_groups');
    }
};
