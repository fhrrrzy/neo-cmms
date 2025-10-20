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
        Schema::dropIfExists('equipment_work_order_materials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This table is no longer needed - it was split into equipment_work_orders and equipment_materials
        // No need to recreate it
    }
};
