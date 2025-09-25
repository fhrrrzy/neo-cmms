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
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->string('plant_code')->unique();
            $table->foreignId('regional_id')->constrained('regions');
            $table->string('name');
            $table->unsignedInteger('kaps_terpasang');
            $table->unsignedInteger('faktor_koreksi_kaps');
            $table->unsignedInteger('faktor_koreksi_utilitas');
            $table->unsignedTinyInteger('unit');
            $table->boolean('instalasi_bunch_press');
            $table->boolean('pln_isasi');
            $table->boolean('cofiring');
            $table->boolean('hidden_pica_cost');
            $table->boolean('hidden_pica_cpo');
            $table->unsignedTinyInteger('jenis');
            $table->unsignedInteger('kaps_terpasang_sf');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
