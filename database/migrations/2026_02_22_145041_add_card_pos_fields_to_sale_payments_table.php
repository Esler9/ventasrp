<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->foreignId('card_pos_terminal_id')->nullable()->after('bank_account_id')->constrained('bank_pos_terminals')->nullOnDelete();
            $table->decimal('commission_percent', 5, 2)->nullable()->after('reference');
            $table->decimal('commission_amount', 12, 2)->nullable()->after('commission_percent');
            $table->decimal('net_amount', 12, 2)->nullable()->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('card_pos_terminal_id');
            $table->dropColumn(['commission_percent', 'commission_amount', 'net_amount']);
        });
    }
};
