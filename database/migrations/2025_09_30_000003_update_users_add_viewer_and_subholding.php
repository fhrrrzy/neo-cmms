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
        // Add subholding_id
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'subholding_id')) {
                $table->foreignId('subholding_id')->nullable()->after('regional_id')->constrained('regions')->nullOnDelete();
            }
        });

        // Extend enum for role to include viewer (MySQL-specific)
        try {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('superadmin','pks','viewer') NOT NULL DEFAULT 'pks'");
        } catch (\Throwable $e) {
            // Fallback: ignore if DB does not support altering enum this way
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop subholding_id
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'subholding_id')) {
                $table->dropForeign(['subholding_id']);
                $table->dropColumn('subholding_id');
            }
        });

        // Attempt to revert enum (optional)
        try {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('superadmin','pks') NOT NULL DEFAULT 'pks'");
        } catch (\Throwable $e) {
        }
    }
};
