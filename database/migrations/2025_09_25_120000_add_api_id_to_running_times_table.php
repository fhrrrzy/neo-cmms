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
            // Replace api_id with ims_id
            if (Schema::hasColumn('running_times', 'api_id')) {
                $table->renameColumn('api_id', 'ims_id');
            } else {
                $table->string('ims_id', 50)->nullable()->after('id');
            }

            $table->unique('ims_id');
            $table->index('ims_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('running_times', function (Blueprint $table) {
            // Rollback ims_id to api_id
            $table->dropIndex(['ims_id']);
            $table->dropUnique(['ims_id']);
            if (Schema::hasColumn('running_times', 'ims_id')) {
                $table->renameColumn('ims_id', 'api_id');
            }
        });
    }
};
