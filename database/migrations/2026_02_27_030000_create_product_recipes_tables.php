<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_recipes')) {
            Schema::create('product_recipes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_recipe_items')) {
            Schema::create('product_recipe_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_recipe_id')->constrained('product_recipes')->cascadeOnDelete();
                $table->foreignId('ingredient_product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->timestamps();

                $table->unique(['product_recipe_id', 'ingredient_product_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_recipe_items')) {
            Schema::drop('product_recipe_items');
        }
        if (Schema::hasTable('product_recipes')) {
            Schema::drop('product_recipes');
        }
    }
};
