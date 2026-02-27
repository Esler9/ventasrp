<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_accounts', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('restaurant_table_id')->constrained('sales')->nullOnDelete();
            $table->foreignId('settled_by_user_id')->nullable()->after('opened_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
            $table->dropConstrainedForeignId('settled_by_user_id');
            $table->dropColumn('settled_at');
        });
    }
};
