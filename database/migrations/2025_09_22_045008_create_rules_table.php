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
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('equipment_group_id')->nullable()->constrained('equipment_groups')->onDelete('cascade');
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->onDelete('cascade');
            $table->json('rules'); // JSON field for storing rules array with 'number' and 'action'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure either equipment_group_id or equipment_id is set, but not both
            $table->index(['equipment_group_id', 'equipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
