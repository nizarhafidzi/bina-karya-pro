<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus Tabel Lama (Yang Strukturnya Salah/Usang dari Phase 2)
        // Kita gunakan foreign_key_checks = 0 untuk menghindari error constraint saat drop
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('item_realizations');
        Schema::dropIfExists('weekly_realizations');
        Schema::dropIfExists('project_schedules');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Buat Ulang Tabel 'project_schedules' (Struktur Phase 4 - Item Base)
        Schema::create('project_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
            $table->integer('week'); // Minggu ke-n
            $table->decimal('progress_plan', 5, 2); // Rencana % item ini
            $table->timestamps();
        });

        // 3. Buat Ulang Tabel 'weekly_realizations' (Struktur Phase 4 - Ada team_id)
        Schema::create('weekly_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('week');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        // 4. Buat Ulang Tabel 'item_realizations' (Struktur Phase 4)
        Schema::create('item_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_realization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
            $table->decimal('progress_this_week', 5, 2)->default(0);
            $table->decimal('progress_cumulative', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Rollback logikanya cukup drop saja
        Schema::dropIfExists('item_realizations');
        Schema::dropIfExists('weekly_realizations');
        Schema::dropIfExists('project_schedules');
    }
};