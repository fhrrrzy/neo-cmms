<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the enum to include 'work_order'
        DB::statement("ALTER TABLE `api_sync_logs` MODIFY COLUMN `sync_type` ENUM('equipment', 'running_time', 'work_order', 'full') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE `api_sync_logs` MODIFY COLUMN `sync_type` ENUM('equipment', 'running_time', 'full') NOT NULL");
    }
};