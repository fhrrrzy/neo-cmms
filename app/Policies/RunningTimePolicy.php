<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RunningTime;

class RunningTimePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, RunningTime $runningTime): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, RunningTime $runningTime): bool
    {
        return false;
    }
    public function delete(User $user, RunningTime $runningTime): bool
    {
        return false;
    }
    public function restore(User $user, RunningTime $runningTime): bool
    {
        return false;
    }
    public function forceDelete(User $user, RunningTime $runningTime): bool
    {
        return false;
    }
}





