<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Costo promedio ponderado (CPP) del producto al momento de la venta.
            // Permite calcular costo de ventas (COGS) y margen bruto por línea.
            $table->decimal('unit_cost', 12, 4)->default(0)->after('original_price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
