<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah owner_id ke tabel projects (One-to-One relationship context)
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'owner_id')) {
                // Owner adalah User, bukan Team
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        // 2. Buat tabel pivot untuk Site Managers (One Project has Many Site Managers)
        if (!Schema::hasTable('project_site_managers')) {
            Schema::create('project_site_managers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 3. Tambah limit user di tabel teams (Untuk Super Admin membatasi Tenant)
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'max_users')) {
                $table->integer('max_users')->default(5)->after('name'); // Default 5 user per tim
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('max_users');
        });
        Schema::dropIfExists('project_site_managers');
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }
};