<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PERBAIKAN: Gunakan Helper Schema agar kompatibel dengan SQLite (Testing) & MySQL (Production)
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('item_realizations');
        Schema::dropIfExists('weekly_realizations');
        Schema::dropIfExists('project_schedules');

        Schema::enableForeignKeyConstraints();
        // ---------------------------------------------------------

        // 2. Buat Ulang Tabel 'project_schedules'
        Schema::create('project_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
            $table->integer('week');
            $table->decimal('progress_plan', 5, 2);
            $table->timestamps();
        });

        // 3. Buat Ulang Tabel 'weekly_realizations'
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

        // 4. Buat Ulang Tabel 'item_realizations'
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
        Schema::dropIfExists('item_realizations');
        Schema::dropIfExists('weekly_realizations');
        Schema::dropIfExists('project_schedules');
    }
};