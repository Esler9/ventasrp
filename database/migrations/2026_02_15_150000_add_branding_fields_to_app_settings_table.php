<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('app_logo_path')->nullable()->after('app_icon_color');
            $table->string('primary_color', 20)->default('#0773A4')->after('app_logo_path');
            $table->string('secondary_color', 20)->default('#0EA5E9')->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['app_logo_path', 'primary_color', 'secondary_color']);
        });
    }
};
