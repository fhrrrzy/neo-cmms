<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('plant_id')->constrained('stations')->nullOnDelete();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('plant_id')->constrained('stations')->nullOnDelete();
            $table->index(['plant_id', 'station_id']);
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
            $table->dropColumn('station_id');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
            $table->dropIndex(['plant_id', 'station_id']);
            $table->dropColumn('station_id');
        });
    }
};
