<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\Project;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Filament\Facades\Filament;

class Phase5UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed Roles sederhana untuk testing
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'tenant_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'site_manager', 'guard_name' => 'web']);
        Role::create(['name' => 'project_owner', 'guard_name' => 'web']);
    }

    /** @test */
    public function tenant_cannot_exceed_max_users_quota()
    {
        // 1. Setup Team dengan Limit 2 User
        $team = Team::factory()->create(['max_users' => 2]);
        
        // 2. Buat Tenant Admin (User ke-1)
        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant_admin');
        $team->members()->attach($tenantAdmin);

        // 3. Tambah User ke-2 (Masih dalam batas)
        $user2 = User::factory()->create();
        $team->members()->attach($user2);

        // Set konteks Tenant untuk Filament
        Filament::setTenant($team);

        // 4. Coba Create User ke-3 (Harusnya Gagal Policy Check)
        // Kita simulasikan cek policy manual karena test HTTP Filament butuh setup kompleks
        $policy = new \App\Policies\UserPolicy();
        
        // Assert: Create user saat kuota penuh (2/2) harusnya FALSE
        $this->assertFalse(
            $policy->create($tenantAdmin), 
            'Tenant Admin seharusnya TIDAK BISA membuat user baru jika kuota penuh.'
        );

        // 5. Update Limit jadi 5
        $team->update(['max_users' => 5]);
        $team->refresh();

        // Assert: Sekarang harusnya TRUE
        $this->assertTrue(
            $policy->create($tenantAdmin),
            'Tenant Admin seharusnya BISA membuat user baru jika kuota ditambah.'
        );
    }

    /** @test */
    public function project_owner_dropdown_logic_is_correct()
    {
        // 1. Setup Team
        $team = Team::factory()->create();
        
        // 2. Buat 3 User di Team tersebut
        $admin = User::factory()->create(['name' => 'Si Admin']);
        $ownerCandidate = User::factory()->create(['name' => 'Calon Owner']);
        $managerCandidate = User::factory()->create(['name' => 'Calon Manager']);

        $team->members()->attach([$admin->id, $ownerCandidate->id, $managerCandidate->id]);

        // 3. Assign Roles
        $admin->assignRole('tenant_admin');
        $ownerCandidate->assignRole('project_owner');     // <--- Target Kita
        $managerCandidate->assignRole('site_manager');    // <--- Bukan Target

        // Set Konteks Tenant
        Filament::setTenant($team);

        // 4. Simulasi Query Dropdown (Logic dari ProjectResource)
        // Code ini meniru logic: $team->members()->whereHas('roles', 'project_owner')
        $options = $team->members()
            ->whereHas('roles', fn ($q) => $q->where('name', 'project_owner'))
            ->pluck('name', 'id');

        // 5. Assertions
        $this->assertTrue($options->contains('Calon Owner'), 'User dengan role Project Owner harus muncul.');
        $this->assertFalse($options->contains('Calon Manager'), 'User dengan role Site Manager TIDAK BOLEH muncul di dropdown Owner.');
        $this->assertFalse($options->contains('Si Admin'), 'User Tenant Admin TIDAK BOLEH muncul di dropdown Owner.');
    }

    /** @test */
    public function super_admin_can_access_global_users()
    {
        // 1. Buat Super Admin
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // 2. Buat User Acak di Team lain
        $randomUser = User::factory()->create();

        // 3. Cek Policy ViewAny
        $policy = new \App\Policies\UserPolicy();

        $this->assertTrue(
            $policy->viewAny($superAdmin),
            'Super Admin harus bisa melihat daftar user global.'
        );
    }
}