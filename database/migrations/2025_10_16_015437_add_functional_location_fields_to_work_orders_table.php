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
            $table->string('functional_location', 255)->nullable()->after('equipment_number')->comment('Functional location code from API');
            $table->string('functional_location_description', 255)->nullable()->after('functional_location')->comment('Functional location description from API');

            // Add index for functional location searches
            $table->index('functional_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['functional_location']);
            $table->dropColumn(['functional_location', 'functional_location_description']);
        });
    }
};
