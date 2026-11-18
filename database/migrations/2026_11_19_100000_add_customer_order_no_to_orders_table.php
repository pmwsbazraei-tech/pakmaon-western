<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nombor order yang diisi pelanggan (wajib di borang; nullable untuk rekod lama)
            $table->string('customer_order_no', 50)->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_order_no');
        });
    }
};
