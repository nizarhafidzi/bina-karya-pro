<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Resource; // Pastikan Model Resource di-import
use App\Models\Team;
use App\Models\TeamSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. SETUP INFRASTRUKTUR (Role & Plan)
        // ==========================================
        
        // Setup Roles
        $roles = ['Super Admin', 'Tenant Admin', 'Site Manager', 'Project Owner'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Setup Plans
        $basicPlan = Plan::create([
            'name' => 'Basic Plan', 'slug' => 'basic', 
            'price_monthly' => 500000, 'max_projects' => 3
        ]);
        
        $proPlan = Plan::create([
            'name' => 'Pro Plan', 'slug' => 'pro', 
            'price_monthly' => 1000000, 'max_projects' => 10
        ]);

        // ==========================================
        // 2. SETUP USER & TEAM (Tenant ID 1 Dibuat Disini)
        // ==========================================

        // --- User 1: Super Admin ---
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@saas.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Super Admin');

        // --- User 2: Tenant Aktif (ID Team = 1) ---
        $userActive = User::create([
            'name' => 'Bos Sukses',
            'email' => 'bos@sukses.com',
            'password' => bcrypt('password'),
        ]);
        
        // Disini Team ID 1 Tercipta!
        $teamActive = Team::create([
            'name' => 'PT Sukses Jaya',
            'slug' => 'sukses-jaya',
            'owner_id' => $userActive->id
        ]);

        $userActive->teams()->attach($teamActive);
        $userActive->assignRole('Tenant Admin');

        // Subscription Aktif
        TeamSubscription::create([
            'team_id' => $teamActive->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        // --- User 3: Tenant Expired (ID Team = 2) ---
        $userExpired = User::create([
            'name' => 'Bos Macet',
            'email' => 'bos@macet.com',
            'password' => bcrypt('password'),
        ]);

        $teamExpired = Team::create([
            'name' => 'CV Macet Total',
            'slug' => 'macet-total',
            'owner_id' => $userExpired->id
        ]);

        $userExpired->teams()->attach($teamExpired);
        $userExpired->assignRole('Tenant Admin');

        TeamSubscription::create([
            'team_id' => $teamExpired->id,
            'plan_id' => $basicPlan->id,
            'status' => 'expired',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        // ==========================================
        // 3. SETUP MASTER DATA RESOURCE (Setelah Team Ada)
        // ==========================================

        // A. Resource GLOBAL (Punya System - team_id NULL)
        // Kita masukkan manual tanpa Eloquent agar Trait tidak mengganggu (Opsional, tapi lebih aman)
        // Atau pakai Eloquent dengan 'team_id' => null explicit
        $globals = [
            ['name' => 'Semen Portland (SNI Global)', 'unit' => 'zak', 'category' => 'material', 'default_price' => 65000],
            ['name' => 'Pasir Beton (SNI Global)', 'unit' => 'm3', 'category' => 'material', 'default_price' => 250000],
            ['name' => 'Pekerja (SNI Global)', 'unit' => 'OH', 'category' => 'labor', 'default_price' => 120000],
        ];

        foreach ($globals as $item) {
            Resource::create(array_merge($item, ['team_id' => null]));
        }

        // B. Resource CUSTOM (Punya PT Sukses Jaya - team_id 1)
        // Kita gunakan $teamActive->id yang sudah pasti ada variabelnya di atas
        $customs = [
            ['name' => 'Semen Tiga Roda (Custom PT Sukses)', 'unit' => 'zak', 'category' => 'material', 'default_price' => 68000],
            ['name' => 'Granit Mewah (Custom PT Sukses)', 'unit' => 'm2', 'category' => 'material', 'default_price' => 185000],
        ];

        foreach ($customs as $item) {
            Resource::create(array_merge($item, ['team_id' => $teamActive->id]));
        }
    }
}