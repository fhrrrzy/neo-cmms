<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('running_times', function (Blueprint $table) {
            $table->string('api_id', 50)->nullable()->after('id');
            $table->unique('api_id');
            $table->index('api_id');
        });
    }

    public function down(): void
    {
        Schema::table('running_times', function (Blueprint $table) {
            $table->dropIndex(['api_id']);
            $table->dropUnique(['api_id']);
            $table->dropColumn('api_id');
        });
    }
};
