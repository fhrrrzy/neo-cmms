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
        Schema::create('equipment_work_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();

            // Work Order fields
            $table->string('order_number', 50)->nullable();
            $table->string('equipment_number', 50)->nullable();
            $table->string('material_description', 255)->nullable();

            // Material fields
            $table->string('material_number', 50)->nullable();
            $table->string('reservation_number', 50)->nullable();
            $table->string('reservation_item', 10)->nullable();
            $table->string('reservation_type', 10)->nullable();

            // Common fields
            $table->string('requirement_type', 10)->nullable();
            $table->string('reservation_status', 5)->nullable();
            $table->string('storage_location', 50)->nullable();
            $table->date('requirement_date')->nullable();
            $table->decimal('requirement_qty', 18, 3)->nullable();
            $table->string('unit_of_measure', 20)->nullable();
            $table->string('debit_credit_indicator', 5)->nullable();
            $table->decimal('withdrawn_qty', 18, 3)->nullable();
            $table->decimal('withdrawn_value', 18, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('movement_type', 10)->nullable();
            $table->string('gl_account', 50)->nullable();
            $table->string('receiving_plant', 50)->nullable();
            $table->string('receiving_storage_loc', 50)->nullable();

            // Equipment Material specific fields
            $table->string('deletion_flag', 5)->nullable();
            $table->string('goods_receipt_flag', 5)->nullable();
            $table->string('final_issue_flag', 5)->nullable();
            $table->string('error_flag', 5)->nullable();
            $table->string('production_supply_area', 50)->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->string('storage_bin', 50)->nullable();
            $table->string('special_stock_indicator', 10)->nullable();
            $table->decimal('issued_qty', 18, 3)->nullable();
            $table->decimal('entry_qty', 18, 3)->nullable();
            $table->string('entry_uom', 20)->nullable();
            $table->string('planned_order', 50)->nullable();
            $table->string('purchase_requisition', 50)->nullable();
            $table->string('purchase_requisition_item', 10)->nullable();
            $table->string('production_order', 50)->nullable();

            // Equipment Work Order specific fields
            $table->string('quantity_is_fixed', 5)->nullable();
            $table->decimal('qty_in_unit_of_entry', 18, 3)->nullable();
            $table->string('unit_of_entry', 20)->nullable();
            $table->decimal('qty_for_avail_check', 18, 3)->nullable();
            $table->string('goods_recipient', 100)->nullable();
            $table->string('material_group', 50)->nullable();
            $table->string('acct_manually', 5)->nullable();
            $table->string('commitment_item_1', 50)->nullable();
            $table->string('commitment_item_2', 50)->nullable();
            $table->string('funds_center', 50)->nullable();
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->decimal('service_duration', 10, 2)->nullable();
            $table->string('service_dur_unit', 10)->nullable();

            // API timestamps
            $table->timestamp('api_created_at')->nullable();
            $table->timestamp('api_updated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['plant_id', 'requirement_date']);
            $table->index('equipment_number');
            $table->index('material_number');
            $table->index('order_number');
            $table->index('production_order');
            $table->index(['order_number', 'material_number'], 'ewom_order_material_idx'); // Composite key for upsert
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_work_order_materials');
    }
};
