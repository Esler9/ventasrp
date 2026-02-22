<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->foreignId('related_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('movement_date');
            $table->string('type', 30); // deposit, withdrawal, transfer_out, transfer_in
            $table->decimal('amount', 12, 2);
            $table->string('description', 255);
            $table->string('reference', 100)->nullable();
            $table->string('transfer_group', 64)->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'movement_date']);
            $table->index('type');
            $table->index('transfer_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_movements');
    }
};
