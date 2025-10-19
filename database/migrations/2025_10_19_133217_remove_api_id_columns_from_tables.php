<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
        return array_key_exists($index, $indexes);
    }
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove ims_id from work_orders table
        if (Schema::hasColumn('work_orders', 'ims_id')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->dropColumn('ims_id');
            });
        }

        // Remove api_id from equipment table
        if (Schema::hasColumn('equipment', 'api_id')) {
            Schema::table('equipment', function (Blueprint $table) {
                $table->dropColumn('api_id');
            });
        }

        // Remove ims_id from running_times table
        if (Schema::hasColumn('running_times', 'ims_id')) {
            Schema::table('running_times', function (Blueprint $table) {
                $table->dropColumn('ims_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back API ID columns
        Schema::table('running_times', function (Blueprint $table) {
            $table->string('ims_id', 50)->nullable();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->string('api_id', 255)->nullable();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('ims_id', 50)->nullable();
        });
    }
};
