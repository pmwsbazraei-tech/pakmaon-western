<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Pak Maon Western');
            $table->decimal('target_sales', 12, 2)->default(30000);
            $table->decimal('target_margin', 5, 2)->default(20);
            $table->integer('target_roi_months')->default(12);
            $table->decimal('epf_pct', 5, 2)->default(13);
            $table->decimal('socso_pct', 5, 2)->default(1.75);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
