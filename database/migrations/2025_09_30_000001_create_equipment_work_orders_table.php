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
        Schema::create('equipment_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('ims_id', 50)->unique();
            $table->string('reservation', 50)->nullable();
            $table->string('requirement_type', 10)->nullable();
            $table->string('reservation_status', 5)->nullable();
            $table->string('item_deleted', 5)->nullable();
            $table->string('movement_allowed', 5)->nullable();
            $table->string('final_issue', 5)->nullable();
            $table->string('missing_part', 5)->nullable();
            $table->string('material', 50)->nullable();
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->string('storage_location', 50)->nullable();
            $table->date('requirements_date')->nullable();
            $table->decimal('requirement_quantity', 18, 3)->nullable();
            $table->string('base_unit_of_measure', 20)->nullable();
            $table->string('debit_credit_ind', 5)->nullable();
            $table->string('quantity_is_fixed', 5)->nullable();
            $table->decimal('quantity_withdrawn', 18, 3)->nullable();
            $table->decimal('value_withdrawn', 18, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('qty_in_unit_of_entry', 18, 3)->nullable();
            $table->string('unit_of_entry', 20)->nullable();
            $table->string('movement_type', 10)->nullable();
            $table->string('gl_account', 50)->nullable();
            $table->string('receiving_plant', 50)->nullable();
            $table->string('receiving_storage_location', 50)->nullable();
            $table->decimal('qty_for_avail_check', 18, 3)->nullable();
            $table->string('goods_recipient', 100)->nullable();
            $table->string('material_group', 50)->nullable();
            $table->string('acct_manually', 5)->nullable();
            $table->string('commitment_item_1', 50)->nullable();
            $table->string('funds_center', 50)->nullable();
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->decimal('service_duration', 10, 2)->nullable();
            $table->string('service_dur_unit', 10)->nullable();
            $table->timestamp('api_updated_at')->nullable();
            $table->string('commitment_item_2', 50)->nullable();
            $table->string('order_number', 50)->nullable();
            $table->string('equipment_number', 50)->nullable();
            $table->timestamps();

            $table->index(['plant_id', 'requirements_date']);
            $table->index('equipment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_work_orders');
    }
};
