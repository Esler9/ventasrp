<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurant_account_items')) {
            if (! Schema::hasColumn('restaurant_account_items', 'canceled_by_user_id')) {
                Schema::table('restaurant_account_items', function (Blueprint $table) {
                    $table->foreignId('canceled_by_user_id')
                        ->nullable()
                        ->after('added_by_user_id')
                        ->constrained('users')
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('restaurant_account_items', 'canceled_at')) {
                Schema::table('restaurant_account_items', function (Blueprint $table) {
                    $table->timestamp('canceled_at')->nullable()->after('served_at');
                });
            }

            if (! Schema::hasColumn('restaurant_account_items', 'cancel_reason')) {
                Schema::table('restaurant_account_items', function (Blueprint $table) {
                    $table->text('cancel_reason')->nullable()->after('canceled_at');
                });
            }
        }

        if (! Schema::hasTable('restaurant_item_consumptions')) {
            Schema::create('restaurant_item_consumptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_account_item_id')
                    ->constrained('restaurant_account_items')
                    ->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->string('source_type', 20)->default('recipe'); // recipe, product
                $table->foreignId('reversed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();

                $table->index(['restaurant_account_item_id', 'reversed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_item_consumptions');

        if (Schema::hasTable('restaurant_account_items')) {
            if (Schema::hasColumn('restaurant_account_items', 'canceled_by_user_id')) {
                Schema::table('restaurant_account_items', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('canceled_by_user_id');
                });
            }

            $columnsToDrop = [];
            if (Schema::hasColumn('restaurant_account_items', 'canceled_at')) {
                $columnsToDrop[] = 'canceled_at';
            }
            if (Schema::hasColumn('restaurant_account_items', 'cancel_reason')) {
                $columnsToDrop[] = 'cancel_reason';
            }

            if (! empty($columnsToDrop)) {
                Schema::table('restaurant_account_items', function (Blueprint $table) use ($columnsToDrop) {
                    $table->dropColumn($columnsToDrop);
                });
            }
        }
    }
};
