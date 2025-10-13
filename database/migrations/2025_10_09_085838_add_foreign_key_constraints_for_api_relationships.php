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
        // Add foreign key constraints for API relationships (only if they don't exist)

        // 1. running_times.equipment_number → equipment.equipment_number
        if (!$this->foreignKeyExists('running_times', 'equipment_number')) {
            Schema::table('running_times', function (Blueprint $table) {
                $table->foreign('equipment_number')
                    ->references('equipment_number')
                    ->on('equipment')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        // 2. work_orders.equipment_number → equipment.equipment_number
        if (!$this->foreignKeyExists('work_orders', 'equipment_number')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->foreign('equipment_number')
                    ->references('equipment_number')
                    ->on('equipment')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }

        // 3. equipment_work_orders.equipment_number → equipment.equipment_number
        if (!$this->foreignKeyExists('equipment_work_orders', 'equipment_number')) {
            Schema::table('equipment_work_orders', function (Blueprint $table) {
                $table->foreign('equipment_number')
                    ->references('equipment_number')
                    ->on('equipment')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }

        // 4. equipment_work_orders.order_number → work_orders.order
        if (!$this->foreignKeyExists('equipment_work_orders', 'order_number')) {
            Schema::table('equipment_work_orders', function (Blueprint $table) {
                $table->foreign('order_number')
                    ->references('order')
                    ->on('work_orders')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }

        // 5. equipment_materials.equipment_number → equipment.equipment_number
        if (!$this->foreignKeyExists('equipment_materials', 'equipment_number')) {
            Schema::table('equipment_materials', function (Blueprint $table) {
                $table->foreign('equipment_number')
                    ->references('equipment_number')
                    ->on('equipment')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }

        // 6. equipment_materials.reservation_number → equipment_work_orders.reservation
        if (!$this->foreignKeyExists('equipment_materials', 'reservation_number')) {
            // First, ensure the referenced column has an index
            Schema::table('equipment_work_orders', function (Blueprint $table) {
                $table->index('reservation', 'equipment_work_orders_reservation_index');
            });

            Schema::table('equipment_materials', function (Blueprint $table) {
                $table->foreign('reservation_number')
                    ->references('reservation')
                    ->on('equipment_work_orders')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Check if a foreign key constraint exists
     */
    private function foreignKeyExists(string $table, string $column): bool
    {
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = ? 
             AND COLUMN_NAME = ? 
             AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $column]
        );

        return count($constraints) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints

        Schema::table('running_times', function (Blueprint $table) {
            $table->dropForeign(['equipment_number']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['equipment_number']);
        });

        Schema::table('equipment_work_orders', function (Blueprint $table) {
            $table->dropForeign(['equipment_number']);
            $table->dropForeign(['order_number']);
        });

        Schema::table('equipment_materials', function (Blueprint $table) {
            $table->dropForeign(['equipment_number']);
            $table->dropForeign(['reservation_number']);
        });

        Schema::table('equipment_work_orders', function (Blueprint $table) {
            $table->dropIndex('equipment_work_orders_reservation_index');
        });
    }
};
