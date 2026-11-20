<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Pelarasan bundar ke 5 sen terdekat (boleh negatif), sudah termasuk dalam total_cents
            $table->integer('rounding_cents')->default(0)->after('sst_included_cents');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('rounding_cents');
        });
    }
};
