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
        // Drop existing foreign key and make column nullable, then recreate FK with SET NULL
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign('equipment_equipment_group_id_foreign');
        });

        DB::statement('ALTER TABLE `equipment` MODIFY `equipment_group_id` BIGINT UNSIGNED NULL;');

        Schema::table('equipment', function (Blueprint $table) {
            $table->foreign('equipment_group_id')
                ->references('id')
                ->on('equipment_groups')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to NOT NULL with CASCADE delete
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['equipment_group_id']);
        });

        DB::statement('ALTER TABLE `equipment` MODIFY `equipment_group_id` BIGINT UNSIGNED NOT NULL;');

        Schema::table('equipment', function (Blueprint $table) {
            $table->foreign('equipment_group_id')
                ->references('id')
                ->on('equipment_groups')
                ->cascadeOnDelete();
        });
    }
};


