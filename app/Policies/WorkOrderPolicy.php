<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, WorkOrder $workOrder): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, WorkOrder $workOrder): bool
    {
        return false;
    }
    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return false;
    }
    public function restore(User $user, WorkOrder $workOrder): bool
    {
        return false;
    }
    public function forceDelete(User $user, WorkOrder $workOrder): bool
    {
        return false;
    }
}
















