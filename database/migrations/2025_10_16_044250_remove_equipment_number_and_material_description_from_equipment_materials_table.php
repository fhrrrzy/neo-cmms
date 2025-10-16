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
            // Remove equipment_number column since API does not provide it
            $table->dropForeign(['equipment_number']);
            $table->dropColumn('equipment_number');

            // Remove material_description column since API does not provide it
            $table->dropColumn('material_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_materials', function (Blueprint $table) {
            // Re-add equipment_number column
            $table->string('equipment_number', 50)->nullable()->after('plant_id');
            $table->foreign('equipment_number')->references('equipment_number')->on('equipment')->nullOnDelete();

            // Re-add material_description column
            $table->string('material_description', 255)->nullable()->after('material_number');
        });
    }
};
