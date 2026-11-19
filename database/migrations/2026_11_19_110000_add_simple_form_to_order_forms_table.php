<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_forms', function (Blueprint $table) {
            // Borang ringkas: Nama, Telefon, Nombor order, Cawangan, Emel sahaja (direkod sebagai pickup)
            $table->boolean('simple_form')->default(false)->after('booking_mode');
        });
    }

    public function down(): void
    {
        Schema::table('order_forms', function (Blueprint $table) {
            $table->dropColumn('simple_form');
        });
    }
};
