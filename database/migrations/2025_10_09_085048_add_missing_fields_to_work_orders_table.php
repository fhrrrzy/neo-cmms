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
        Schema::table('work_orders', function (Blueprint $table) {
            // Add missing API fields from work orders response
            $table->string('equipment_number', 50)->nullable()->after('api_updated_at')->comment('Equipment number associated with the work order');
            $table->string('opertn_task_list_no', 50)->nullable()->after('equipment_number')->comment('Operation task list number');

            // Add indexes for commonly searched fields
            $table->index('equipment_number');
            $table->index('opertn_task_list_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['equipment_number']);
            $table->dropIndex(['opertn_task_list_no']);
            $table->dropColumn(['equipment_number', 'opertn_task_list_no']);
        });
    }
};
