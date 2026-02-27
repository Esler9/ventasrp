<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('code', 40)->unique();
            $table->boolean('is_takeaway')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('restaurant_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->cascadeOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('open'); // open, closed
            $table->string('split_type', 20)->default('unique'); // unique, split
            $table->string('label', 120)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_table_id', 'status']);
        });

        Schema::create('restaurant_account_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_account_id')->constrained('restaurant_accounts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->text('note')->nullable();
            $table->string('kitchen_status', 20)->default('pending'); // pending, sent, preparing, ready, served
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();

            $table->index('kitchen_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_account_items');
        Schema::dropIfExists('restaurant_accounts');
        Schema::dropIfExists('restaurant_tables');
    }
};
