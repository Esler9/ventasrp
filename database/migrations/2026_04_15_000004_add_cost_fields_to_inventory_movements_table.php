<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Costo unitario en el momento del movimiento (CPP vigente para ventas, costo de compra para ingresos)
            $table->decimal('unit_cost', 12, 4)->nullable()->after('quantity');
            // Tipo de origen: sale, purchase, adjustment, initial
            $table->string('reference_type', 30)->nullable()->after('unit_cost');
            // ID del registro origen (sale_id, purchase_id, etc.)
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            // Trazabilidad de stock
            $table->integer('stock_before')->default(0)->after('reference_id');
            $table->integer('stock_after')->default(0)->after('stock_before');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'reference_type', 'reference_id', 'stock_before', 'stock_after']);
        });
    }
};
