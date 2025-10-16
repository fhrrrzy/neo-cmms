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
        Schema::table('equipment_materials', function (Blueprint $table) {
            // Drop the foreign key constraint that prevents equipment materials
            // from being saved when reservation doesn't exist in equipment_work_orders
            $table->dropForeign(['reservation_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_materials', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('reservation_number')
                ->references('reservation')
                ->on('equipment_work_orders')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }
};
