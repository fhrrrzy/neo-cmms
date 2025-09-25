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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('ims_id', 50)->unique(); // id from API
            $table->string('mandt', 10)->nullable(); // client
            $table->string('order', 50)->unique(); // order number
            $table->string('order_type', 10)->nullable(); // PM01, etc
            $table->date('created_on')->nullable();
            $table->date('change_date_for_order_master')->nullable();
            $table->text('description')->nullable();
            $table->string('company_code', 10)->nullable();
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->string('plant_code', 20)->nullable(); // original plant code from API
            $table->string('responsible_cctr', 50)->nullable();
            $table->string('order_status', 10)->nullable();
            $table->date('technical_completion')->nullable();
            $table->string('cost_center', 50)->nullable();
            $table->string('profit_center', 50)->nullable();
            $table->string('object_class', 10)->nullable();
            $table->string('main_work_center', 50)->nullable();
            $table->string('notification', 50)->nullable();
            $table->string('cause', 50)->nullable();
            $table->text('cause_text')->nullable();
            $table->string('code_group_problem', 50)->nullable();
            $table->text('item_text')->nullable();
            $table->timestamp('created')->nullable();
            $table->timestamp('released')->nullable();
            $table->string('completed', 10)->nullable(); // X, null
            $table->timestamp('closed')->nullable();
            $table->timestamp('planned_release')->nullable();
            $table->timestamp('planned_completion')->nullable();
            $table->timestamp('planned_closing_date')->nullable();
            $table->timestamp('release')->nullable();
            $table->timestamp('close')->nullable();
            $table->timestamp('api_updated_at')->nullable(); // updated_at from API
            $table->timestamps();

            $table->index(['plant_id', 'order_status']);
            $table->index(['created_on', 'order_type']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
