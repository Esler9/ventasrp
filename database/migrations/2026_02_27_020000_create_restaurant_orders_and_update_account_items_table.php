<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_account_id')->constrained('restaurant_accounts')->cascadeOnDelete();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending, preparing, ready, completed
            $table->timestamp('sent_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_table_id', 'status']);
            $table->index(['restaurant_account_id', 'status']);
        });

        Schema::table('restaurant_account_items', function (Blueprint $table) {
            $table->foreignId('restaurant_order_id')
                ->nullable()
                ->after('restaurant_account_id')
                ->constrained('restaurant_orders')
                ->nullOnDelete();
        });

        DB::table('restaurant_account_items')
            ->where('kitchen_status', 'pending')
            ->whereNull('sent_at')
            ->update(['kitchen_status' => 'draft']);

        DB::table('restaurant_account_items')
            ->where('kitchen_status', 'sent')
            ->update(['kitchen_status' => 'pending']);

    }

    public function down(): void
    {
        Schema::table('restaurant_account_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_order_id');
        });

        Schema::dropIfExists('restaurant_orders');
    }
};
