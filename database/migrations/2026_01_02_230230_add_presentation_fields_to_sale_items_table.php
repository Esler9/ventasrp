<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_presentation_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('presentation_name')->nullable()->after('product_presentation_id');
            $table->unsignedInteger('presentation_factor')->default(1)->after('presentation_name');
            $table->decimal('presentation_price', 12, 2)->default(0)->after('presentation_factor');
            $table->unsignedInteger('presentation_quantity')->default(1)->after('presentation_price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_presentation_id');
            $table->dropColumn(['presentation_name', 'presentation_factor', 'presentation_price', 'presentation_quantity']);
        });
    }
};
