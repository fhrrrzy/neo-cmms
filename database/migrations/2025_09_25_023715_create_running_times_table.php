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
        Schema::create('running_times', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_number', 50);
            $table->date('date');
            $table->foreignId('plant_id')->nullable()->constrained('plants')->onDelete('set null');
            $table->datetime('date_time')->nullable();
            $table->decimal('running_hours', 10, 2)->nullable();
            $table->decimal('counter_reading', 15, 2)->nullable();
            $table->text('maintenance_text')->nullable();
            $table->string('company_code', 50)->nullable();
            $table->string('equipment_description')->nullable();
            $table->string('object_number', 50)->nullable();
            $table->timestamp('api_created_at')->nullable();
            $table->timestamps();

            $table->unique(['equipment_number', 'date']);
            $table->index(['plant_id', 'date']);
            $table->index(['equipment_number', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('running_times');
    }
};
