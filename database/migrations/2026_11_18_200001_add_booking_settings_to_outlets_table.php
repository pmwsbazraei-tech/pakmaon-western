<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tempahan online: setiap outlet ialah cawangan untuk booking meja dan pickup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('whatsapp', 20)->nullable()->after('slug');     // format 60XXXXXXXXX
            $table->string('address')->nullable();
            $table->string('maps_url')->nullable();
            $table->boolean('accepts_orders')->default(false);              // dipaparkan pada landing page
            $table->boolean('booking_enabled')->default(true);
            $table->boolean('pickup_enabled')->default(true);
            $table->boolean('delivery_enabled')->default(false);
            $table->unsignedSmallInteger('slot_minutes')->default(30);
            $table->unsignedSmallInteger('slot_capacity')->default(40);      // jumlah tetamu bagi satu slot
            $table->unsignedSmallInteger('max_guests_per_booking')->default(20);
            $table->unsignedSmallInteger('lead_minutes')->default(60);
            $table->unsignedSmallInteger('advance_days')->default(30);
            $table->unsignedInteger('delivery_fee_cents')->default(0);
        });

        Schema::create('outlet_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');   // 0 = Ahad ... 6 = Sabtu
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->unique(['outlet_id', 'weekday']);
        });

        Schema::create('outlet_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->unique(['outlet_id', 'date']);
        });

        // Akaun staf cawangan: hanya nampak tempahan outlet yang diberikan
        Schema::create('outlet_user', function (Blueprint $table) {
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff');   // manager | staff
            $table->primary(['outlet_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_user');
        Schema::dropIfExists('outlet_closures');
        Schema::dropIfExists('outlet_hours');
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug', 'whatsapp', 'address', 'maps_url', 'accepts_orders', 'booking_enabled', 'pickup_enabled',
                'delivery_enabled', 'slot_minutes', 'slot_capacity', 'max_guests_per_booking', 'lead_minutes',
                'advance_days', 'delivery_fee_cents',
            ]);
        });
    }
};
