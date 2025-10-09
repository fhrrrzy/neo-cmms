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
        // Clean up orphaned data before adding foreign key constraints

        // 1. Clean up equipment_materials with reservation_numbers that don't exist in equipment_work_orders
        DB::statement("
            UPDATE equipment_materials 
            SET reservation_number = NULL 
            WHERE reservation_number IS NOT NULL 
            AND reservation_number NOT IN (
                SELECT DISTINCT reservation 
                FROM equipment_work_orders 
                WHERE reservation IS NOT NULL
            )
        ");

        // 2. Clean up equipment_work_orders with order_numbers that don't exist in work_orders
        DB::statement("
            UPDATE equipment_work_orders 
            SET order_number = NULL 
            WHERE order_number IS NOT NULL 
            AND order_number NOT IN (
                SELECT DISTINCT `order` 
                FROM work_orders 
                WHERE `order` IS NOT NULL
            )
        ");

        // 3. Clean up equipment_materials with equipment_numbers that don't exist in equipment
        DB::statement("
            UPDATE equipment_materials 
            SET equipment_number = NULL 
            WHERE equipment_number IS NOT NULL 
            AND equipment_number NOT IN (
                SELECT DISTINCT equipment_number 
                FROM equipment 
                WHERE equipment_number IS NOT NULL
            )
        ");

        // 4. Clean up equipment_work_orders with equipment_numbers that don't exist in equipment
        DB::statement("
            UPDATE equipment_work_orders 
            SET equipment_number = NULL 
            WHERE equipment_number IS NOT NULL 
            AND equipment_number NOT IN (
                SELECT DISTINCT equipment_number 
                FROM equipment 
                WHERE equipment_number IS NOT NULL
            )
        ");

        // 5. Clean up work_orders with equipment_numbers that don't exist in equipment
        DB::statement("
            UPDATE work_orders 
            SET equipment_number = NULL 
            WHERE equipment_number IS NOT NULL 
            AND equipment_number NOT IN (
                SELECT DISTINCT equipment_number 
                FROM equipment 
                WHERE equipment_number IS NOT NULL
            )
        ");

        // 6. Clean up running_times with equipment_numbers that don't exist in equipment
        DB::statement("
            UPDATE running_times 
            SET equipment_number = NULL 
            WHERE equipment_number IS NOT NULL 
            AND equipment_number NOT IN (
                SELECT DISTINCT equipment_number 
                FROM equipment 
                WHERE equipment_number IS NOT NULL
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data cleanup
    }
};
