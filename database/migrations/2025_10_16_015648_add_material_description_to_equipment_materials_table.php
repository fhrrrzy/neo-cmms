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
            $table->string('material_description', 255)->nullable()->after('material_number')->comment('Material description from API');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_materials', function (Blueprint $table) {
            $table->dropColumn('material_description');
        });
    }
};
