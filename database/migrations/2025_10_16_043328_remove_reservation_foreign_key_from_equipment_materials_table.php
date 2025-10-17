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
        // Check if the foreign key exists before trying to drop it
        // (it was never created in 2025_10_09_085838 migration)
        if ($this->foreignKeyExists('equipment_materials', 'equipment_materials_reservation_number_foreign')) {
            Schema::table('equipment_materials', function (Blueprint $table) {
                // Drop the foreign key constraint that prevents equipment materials
                // from being saved when reservation doesn't exist in equipment_work_orders
                $table->dropForeign(['reservation_number']);
            });
        }
    }

    /**
     * Check if a foreign key exists
     */
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT CONSTRAINT_NAME 
             FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND CONSTRAINT_NAME = ? 
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$databaseName, $table, $constraintName]
        );

        return count($result) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_materials', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('reservation_number')
                ->references('reservation')
                ->on('equipment_work_orders')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }
};
