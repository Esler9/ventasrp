<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->boolean('apply_surcharge')->default(false)->after('reference');
            $table->decimal('surcharge_percent', 5, 2)->nullable()->after('apply_surcharge');
            $table->decimal('surcharge_amount', 12, 2)->nullable()->after('surcharge_percent');
            $table->decimal('base_amount', 12, 2)->nullable()->after('surcharge_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropColumn(['apply_surcharge', 'surcharge_percent', 'surcharge_amount', 'base_amount']);
        });
    }
};
