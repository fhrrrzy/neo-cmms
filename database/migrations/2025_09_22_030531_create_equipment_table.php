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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_number', 50)->unique();
            $table->foreignId('plant_id')->constrained('plants')->onDelete('cascade');
            $table->foreignId('equipment_group_id')->constrained('equipment_groups')->onDelete('cascade');
            $table->string('company_code', 50)->nullable();
            $table->string('equipment_description')->nullable();
            $table->string('object_number', 50)->nullable();
            $table->string('point', 50)->nullable();
            $table->timestamp('api_created_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['plant_id', 'equipment_group_id']);
            $table->index('equipment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
