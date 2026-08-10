<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);        // slug kekal (rujukan receipts.category)
            $table->string('name');           // nama paparan (boleh edit)
            $table->string('icon', 8)->default('📁');
            $table->boolean('is_cogs')->default(false);
            $table->timestamps();

            $table->unique(['outlet_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_categories');
    }
};
