<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Deposit yang diterima secara manual (sen). Baki = total_cents - deposit_cents.
            $table->unsignedInteger('deposit_cents')->default(0)->after('total_cents');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('deposit_cents');
        });
    }
};
