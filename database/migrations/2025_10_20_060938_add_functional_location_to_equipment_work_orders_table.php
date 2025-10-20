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
            $table->string('functional_location', 50)->nullable()->after('equipment_number');
            $table->string('functional_location_description', 255)->nullable()->after('functional_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_work_orders', function (Blueprint $table) {
            $table->dropColumn(['functional_location', 'functional_location_description']);
        });
    }
};
