<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing indexes for foreign key constraints (only if they don't exist)
        
        // Add index on equipment_work_orders.reservation for foreign key
        if (!$this->indexExists('equipment_work_orders', 'equipment_work_orders_reservation_index')) {
            Schema::table('equipment_work_orders', function (Blueprint $table) {
                $table->index('reservation');
            });
        }

        // Add index on work_orders.order for foreign key (check if unique index exists)
        if (!$this->indexExists('work_orders', 'work_orders_order_unique') && 
            !$this->indexExists('work_orders', 'work_orders_order_index')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->index('order');
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select(
            "SHOW INDEX FROM {$table} WHERE Key_name = ?",
            [$indexName]
        );
        
        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_work_orders', function (Blueprint $table) {
            $table->dropIndex(['reservation']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['order']);
        });
    }
};