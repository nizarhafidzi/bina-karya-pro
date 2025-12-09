<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Filter sudah ditangani oleh Trait HasTenancyScope
    }

    public function view(User $user, Project $project): bool
    {
        return $user->teams->contains($project->team_id);
    }

    public function create(User $user): bool
    {
        // Cek limit plan bisa ditambahkan di sini
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->teams->contains($project->team_id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->teams->contains($project->team_id);
    }
}