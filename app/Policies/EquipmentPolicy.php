<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Equipment;

class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Equipment $equipment): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, Equipment $equipment): bool
    {
        return false;
    }
    public function delete(User $user, Equipment $equipment): bool
    {
        return false;
    }
    public function restore(User $user, Equipment $equipment): bool
    {
        return false;
    }
    public function forceDelete(User $user, Equipment $equipment): bool
    {
        return false;
    }
}





