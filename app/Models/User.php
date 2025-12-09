<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasTenancyScope;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guarded = [];

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    

    // --- Filament Tenancy Logic ---

    // 1. Mendapatkan semua tim di mana user ini bergabung
    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    // 2. Mengecek apakah user boleh akses tim tertentu
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams->contains($tenant);
    }

    // 3. Relasi Eloquent
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id');
    }
    
    // --- LOGIC PENENTU AKSES PANEL (CRITICAL) ---
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Panel ADMIN (Super Admin Only)
        if ($panel->getId() === 'admin') {
            return $this->hasRole('Super Admin');
        }

        // 2. Panel APP (Tenant Admin Only)
        if ($panel->getId() === 'app') {
            // Site Manager & Project Owner DILARANG MASUK SINI
            // Mereka punya halaman khusus di /project/{id}/dashboard
            return $this->hasRole(['Super Admin', 'Tenant Admin']);
        }

        return false;
    }

    // Relasi User sebagai Owner Project
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    // Relasi User sebagai Site Manager
    public function managedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_site_managers', 'user_id', 'project_id');
    }
}