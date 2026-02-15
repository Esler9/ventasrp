<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('sale_code', 50)->nullable()->unique()->after('id');
            $table->string('customer_name', 120)->default('CF')->after('user_id');
            $table->foreignId('cash_session_id')->nullable()->after('customer_name')->constrained('cash_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_session_id');
            $table->dropUnique(['sale_code']);
            $table->dropColumn(['sale_code', 'customer_name']);
        });
    }
};
