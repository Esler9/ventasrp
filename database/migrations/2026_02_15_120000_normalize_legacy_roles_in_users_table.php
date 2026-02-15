<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'owner_manager']);
        DB::table('users')->where('role', 'seller')->update(['role' => 'seller_cashier']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'owner_manager')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'seller_cashier')->update(['role' => 'seller']);
    }
};
