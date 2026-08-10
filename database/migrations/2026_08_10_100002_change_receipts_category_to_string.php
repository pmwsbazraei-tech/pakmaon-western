<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tukar enum -> string supaya kunci kategori custom (dari DB) boleh disimpan.
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('category', 60)->change();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->enum('category', ['bahan', 'hardware', 'utiliti', 'packaging', 'marketing', 'maintenance', 'gas', 'lain'])->change();
        });
    }
};
