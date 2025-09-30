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
        Schema::table('api_sync_logs', function (Blueprint $table) {
            // Change enum to string to support new types without future schema changes
            $table->string('sync_type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_sync_logs', function (Blueprint $table) {
            // Best-effort revert: set to enum with known types
            $table->enum('sync_type', ['equipment', 'equipment_material', 'equipment_work_orders', 'running_time', 'work_order', 'full'])->change();
        });
    }
};
