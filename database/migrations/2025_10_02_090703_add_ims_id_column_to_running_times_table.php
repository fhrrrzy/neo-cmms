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
        Schema::table('running_times', function (Blueprint $table) {
            if (!Schema::hasColumn('running_times', 'ims_id')) {
                $table->string('ims_id', 50)->nullable()->after('id');
                $table->unique('ims_id');
                $table->index('ims_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('running_times', function (Blueprint $table) {
            if (Schema::hasColumn('running_times', 'ims_id')) {
                $table->dropUnique(['ims_id']);
                $table->dropIndex(['ims_id']);
                $table->dropColumn('ims_id');
            }
        });
    }
};
