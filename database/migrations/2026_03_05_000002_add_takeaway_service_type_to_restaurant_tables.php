<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->string('takeaway_service_type', 20)
                ->nullable()
                ->after('is_takeaway');
        });

        DB::table('restaurant_tables')
            ->where('is_takeaway', true)
            ->update(['takeaway_service_type' => 'delivery']);
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropColumn('takeaway_service_type');
        });
    }
};

