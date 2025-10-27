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
        Schema::create('daily_plant_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->onDelete('cascade');
            $table->date('date');
            $table->integer('is_mengolah')->default(0); // 0 or 1
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['plant_id', 'date']);

            // Indexes for better query performance
            $table->index('plant_id');
            $table->index('date');
            $table->index('is_mengolah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_plant_data');
    }
};
