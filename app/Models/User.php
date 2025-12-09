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

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasRoles; // Spatie

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
        return $this->belongsToMany(Team::class);
    }
    
    // --- Access Logic ---
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            // Hanya Super Admin yg bisa masuk /admin
            return $this->hasRole('Super Admin');
        }
        
        // Tenant panel (/app) bisa diakses semua user yang punya team
        return true; 
    }
}