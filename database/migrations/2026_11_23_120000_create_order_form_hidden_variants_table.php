<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Variant yang TIDAK dipaparkan pada sesuatu landing page. Tiada baris = semua variant produk dipaparkan,
 * jadi pilihan baharu dalam variant group terus muncul tanpa perlu ditick semula.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_form_hidden_variants', function (Blueprint $table) {
            $table->foreignId('order_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->primary(['order_form_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_form_hidden_variants');
    }
};
