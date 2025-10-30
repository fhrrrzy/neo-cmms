<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add UUID to regions table
        Schema::table('regions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        // Add UUID to plants table
        Schema::table('plants', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        // Generate UUIDs for existing records
        DB::table('regions')->whereNull('uuid')->orderBy('id')->chunk(100, function ($regions) {
            foreach ($regions as $region) {
                DB::table('regions')
                    ->where('id', $region->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        });

        DB::table('plants')->whereNull('uuid')->orderBy('id')->chunk(100, function ($plants) {
            foreach ($plants as $plant) {
                DB::table('plants')
                    ->where('id', $plant->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        });

        // Make UUID columns non-nullable after populating
        Schema::table('regions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('plants', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
