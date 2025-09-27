<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Equipment;
use App\Policies\EquipmentPolicy;
use App\Models\WorkOrder;
use App\Policies\WorkOrderPolicy;
use App\Models\RunningTime;
use App\Policies\RunningTimePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Equipment::class, EquipmentPolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
        Gate::policy(RunningTime::class, RunningTimePolicy::class);
    }
}
