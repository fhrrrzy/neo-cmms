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
        Schema::table('equipment_work_orders', function (Blueprint $table) {
            $table->string('material_description', 255)->nullable()->after('material');
            $table->index('material_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_work_orders', function (Blueprint $table) {
            $table->dropIndex(['material_description']);
            $table->dropColumn('material_description');
        });
    }
};
