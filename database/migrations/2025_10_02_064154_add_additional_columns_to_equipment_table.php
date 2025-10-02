<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('api_id', 255)->nullable()->after('id');
            $table->string('mandt', 50)->nullable()->after('api_id');
            $table->string('baujj', 50)->nullable()->after('mandt');
            $table->string('groes', 255)->nullable()->after('baujj');
            $table->string('herst', 255)->nullable()->after('groes');

            $table->index(['api_id', 'equipment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['api_id', 'equipment_number']);
            $table->dropColumn(['api_id', 'mandt', 'baujj', 'groes', 'herst']);
        });
    }
};
