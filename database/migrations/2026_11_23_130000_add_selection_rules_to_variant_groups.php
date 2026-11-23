<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Variant group menjadi add-on:
 * - is_required: pelanggan wajib pilih sekurang-kurangnya 1; jika tidak, boleh langkau.
 * - max_select: maksimum pilihan yang boleh dipilih (1 = pilih satu sahaja).
 * Harga pilihan DITAMBAH atas harga produk. Produk bervariasi lama (tiada harga) diberi harga asas RM0
 * supaya jumlah kekal sama seperti sebelum ini.
 * order_items.options menyimpan pilihan yang dipilih (nama, group, harga) untuk rekod.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variant_groups', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('name');
            $table->unsignedSmallInteger('max_select')->default(1)->after('is_required');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('options')->nullable()->after('variant_name');
        });

        DB::table('products')->where('has_variants', true)->whereNull('price_cents')->update(['price_cents' => 0]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('options');
        });
        Schema::table('variant_groups', function (Blueprint $table) {
            $table->dropColumn(['is_required', 'max_select']);
        });
    }
};
