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
        Schema::create('api_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('sync_type', ['equipment', 'running_time', 'full']);
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled']);
            $table->integer('records_processed')->default(0);
            $table->integer('records_success')->default(0);
            $table->integer('records_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sync_started_at')->nullable();
            $table->timestamp('sync_completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['sync_type', 'status']);
            $table->index('sync_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_sync_logs');
    }
};
