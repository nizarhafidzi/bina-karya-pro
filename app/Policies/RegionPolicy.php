<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    // Hanya Super Admin yang bisa melihat daftar Region
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    // Hanya Super Admin yang bisa melihat detail Region
    public function view(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }

    // Hanya Super Admin yang bisa membuat Region baru
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    // Hanya Super Admin yang bisa edit Region
    public function update(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }

    // Hanya Super Admin yang bisa hapus Region
    public function delete(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }
}