<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Senarai Variant dikongsi: satu variant (nama + harga) boleh ditick pada banyak produk.
 * product_variants kekal sebagai pautan produk ↔ variant (dirujuk oleh pesanan lama),
 * dengan nama/harga disalin dari senarai Variant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('promo_price_cents')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        // Pindah variasi sedia ada ke senarai Variant: satu entri bagi setiap (pemilik, nama, harga, promo)
        $rows = DB::table('product_variants')->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('product_variants.is_active', true)
            ->orderBy('product_variants.product_id')->orderBy('product_variants.sort')
            ->get(['product_variants.*', 'products.user_id']);

        $made = [];
        $names = [];
        foreach ($rows as $r) {
            $key = $r->user_id.'|'.mb_strtolower(trim($r->name)).'|'.$r->price_cents.'|'.($r->promo_price_cents ?? '');
            if (! isset($made[$key])) {
                $nameKey = $r->user_id.'|'.mb_strtolower(trim($r->name));
                $name = isset($names[$nameKey]) ? trim($r->name).' (RM'.number_format($r->price_cents / 100, 2).')' : trim($r->name);
                $names[$nameKey] = true;
                $made[$key] = DB::table('variants')->insertGetId([
                    'user_id' => $r->user_id, 'name' => $name, 'sku' => $r->sku, 'price_cents' => $r->price_cents,
                    'promo_price_cents' => $r->promo_price_cents, 'sort' => count($made), 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('product_variants')->where('id', $r->id)->update(['variant_id' => $made[$key], 'stock' => null, 'max_per_order' => null]);
        }
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
        });
        Schema::dropIfExists('variants');
    }
};
