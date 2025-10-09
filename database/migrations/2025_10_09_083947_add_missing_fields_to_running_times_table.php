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
        Schema::table('running_times', function (Blueprint $table) {
            // Add missing API fields from running time response
            $table->string('mandt', 50)->nullable()->after('ims_id')->comment('MANDT from API - Mandant/Client code');
            $table->string('point', 50)->nullable()->after('mandt')->comment('POINT from API - Point/Measurement point');

            // Add index for commonly searched fields
            $table->index('mandt');
            $table->index('point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('running_times', function (Blueprint $table) {
            $table->dropIndex(['mandt']);
            $table->dropIndex(['point']);
            $table->dropColumn(['mandt', 'point']);
        });
    }
};
