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
        Schema::create('equipment_running_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade');
            $table->foreignId('plant_id')->constrained('plants')->onDelete('cascade');
            $table->string('point', 50)->nullable();
            $table->date('date');
            $table->timestamp('date_time')->nullable();
            $table->text('description')->nullable();
            $table->decimal('running_hours', 10, 2)->default(0);
            $table->decimal('cumulative_hours', 12, 2)->default(0);
            $table->string('company_code', 50)->nullable();
            $table->string('equipment_description')->nullable();
            $table->string('object_number', 50)->nullable();
            $table->timestamp('api_created_at')->nullable();
            $table->timestamps();
            
            $table->unique(['equipment_id', 'date']);
            $table->index(['plant_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_running_times');
    }
};
