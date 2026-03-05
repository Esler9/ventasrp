<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_delivery_riders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('restaurant_accounts', function (Blueprint $table) {
            $table->foreignId('delivery_rider_id')
                ->nullable()
                ->after('restaurant_table_id')
                ->constrained('restaurant_delivery_riders')
                ->nullOnDelete();
            $table->timestamp('delivery_assigned_at')->nullable()->after('delivery_rider_id');
            $table->index(['delivery_rider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_accounts', function (Blueprint $table) {
            $table->dropIndex(['delivery_rider_id', 'status']);
            $table->dropConstrainedForeignId('delivery_rider_id');
            $table->dropColumn('delivery_assigned_at');
        });

        Schema::dropIfExists('restaurant_delivery_riders');
    }
};

