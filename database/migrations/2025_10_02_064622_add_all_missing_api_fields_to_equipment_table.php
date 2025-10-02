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
        Schema::table('equipment', function (Blueprint $table) {
            // Add complete mapping of all API fields

            // Existing fields (already added in previous migration):
            // api_id (ID)
            // mandt (MANDT) 
            // baujj (BAUJJ)
            // groes (GROES)
            // herst (HERST)

            // Additional API fields not yet captured:
            $table->string('mrnug', 50)->nullable()->after('herst')->comment('MRNGU from API');
            $table->string('eqtyp', 50)->nullable()->after('mrnug')->comment('EQTYP from API');
            $table->string('eqart', 100)->nullable()->after('eqtyp')->comment('EQART from API');
            $table->string('maintenance_planner_group', 100)->nullable()->after('eqart')->comment('MAINTAINANCE_PLANNER_GROUP from API');
            $table->string('maintenance_work_center', 100)->nullable()->after('maintenance_planner_group')->comment('MAINTAINANCE_WORK_CENTER from API');
            $table->string('functional_location', 255)->nullable()->after('maintenance_work_center')->comment('FUNCTIONAL_LOCATION from API');
            $table->string('description_func_location', 255)->nullable()->after('functional_location')->comment('DESCRIPTION_FUNC_LOCATION from API');

            // Add index for commonly searched fields
            $table->index(['functional_location', 'maintenance_work_center']);
            $table->index('functional_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['functional_location', 'maintenance_work_center']);
            $table->dropIndex('functional_location');
            $table->dropColumn([
                'mrnug',
                'eqtyp',
                'eqart',
                'maintenance_planner_group',
                'maintenance_work_center',
                'functional_location',
                'description_func_location'
            ]);
        });
    }
};
