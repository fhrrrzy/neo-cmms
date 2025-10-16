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
            // Ensure column exists
            if (!Schema::hasColumn('equipment_materials', 'production_order')) {
                $table->string('production_order', 50)->nullable()->after('purchase_requisition_item');
            }
            $table->foreign('production_order')->references('order')->on('work_orders')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_materials', function (Blueprint $table) {
            $table->dropForeign(['production_order']);
            // Keep the column; only drop FK
        });
    }
};
