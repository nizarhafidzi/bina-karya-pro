<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Team;
use Filament\Facades\Filament;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua user yang bisa akses panel App boleh melihat list user timnya
        return $user->hasRole(['Super Admin', 'Tenant Admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // 1. Super Admin selalu boleh
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // 2. Tenant Admin
        if ($user->hasRole('Tenant Admin')) {
            $currentTeam = Filament::getTenant();
            
            // Jika tidak terdeteksi tenant (misal error context), block
            if (!$currentTeam) {
                return false; 
            }

            // Hitung jumlah member saat ini
            // Pastikan relasi 'members' ada di Team.php
            $currentCount = $currentTeam->members()->count();
            
            // Ambil limit (default 5 jika null)
            $limit = $currentTeam->max_users ?? 5;

            // Logika: Boleh create jika count < limit
            return $currentCount < $limit;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        // 1. Super Admin bebas
        if ($currentUser->hasRole('Super Admin')) {
            return true;
        }

        // 2. Tenant Admin Logic
        if ($currentUser->hasRole('Tenant Admin')) {
            // Ambil Team yang sedang aktif
            $currentTeam = Filament::getTenant();

            // TIDAK BOLEH edit diri sendiri lewat menu ini (ada menu profile terpisah)
            // TIDAK BOLEH edit Super Admin
            if ($targetUser->hasRole('Super Admin')) {
                return false;
            }

            // TIDAK BOLEH edit Pemilik Team (Team Owner) jika login bukan sebagai owner
            // (Mencegah admin biasa mengambil alih tim dari owner asli)
            if ($currentTeam && $currentTeam->user_id === $targetUser->id && $currentUser->id !== $targetUser->id) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $currentUser, User $targetUser): bool
    {
        // Logika sama persis dengan Update
        // Kita proteksi Owner agar tidak terhapus dari timnya sendiri
        if ($currentUser->hasRole('Super Admin')) {
            return true;
        }

        if ($currentUser->hasRole('Tenant Admin')) {
            $currentTeam = Filament::getTenant();

            if ($targetUser->hasRole('Super Admin')) {
                return false;
            }

            // PENTING: Jangan sampai Tenant Admin menghapus Owner Tim!
            if ($currentTeam && $currentTeam->user_id === $targetUser->id) {
                return false;
            }

            // PENTING: Jangan hapus diri sendiri (bunuh diri akun)
            if ($currentUser->id === $targetUser->id) {
                return false;
            }

            return true;
        }

        return false;
    }
}