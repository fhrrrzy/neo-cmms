<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_images', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_number', 50);
            $table->string('name');
            $table->string('filepath');
            $table->timestamps();

            $table->index(['equipment_number']);
            $table->foreign('equipment_number')
                ->references('equipment_number')
                ->on('equipment')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_images');
    }
};


