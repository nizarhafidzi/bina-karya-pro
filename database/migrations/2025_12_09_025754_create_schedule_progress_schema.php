<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update RAB Items (Cek dulu apakah kolom sudah ada)
        Schema::table('rab_items', function (Blueprint $table) {
            if (!Schema::hasColumn('rab_items', 'weight')) {
                $table->decimal('weight', 5, 2)->default(0)->after('total_price');
            }
            if (!Schema::hasColumn('rab_items', 'start_week')) {
                $table->integer('start_week')->nullable()->after('weight');
            }
            if (!Schema::hasColumn('rab_items', 'end_week')) {
                $table->integer('end_week')->nullable()->after('start_week');
            }
        });

        // 2. Project Schedules
        if (!Schema::hasTable('project_schedules')) {
            Schema::create('project_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
                $table->integer('week');
                $table->decimal('progress_plan', 5, 2);
                $table->timestamps();
            });
        }

        // 3. Weekly Realizations
        if (!Schema::hasTable('weekly_realizations')) {
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
        }

        // 4. Item Realizations
        if (!Schema::hasTable('item_realizations')) {
            Schema::create('item_realizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('weekly_realization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
                $table->decimal('progress_this_week', 5, 2)->default(0);
                $table->decimal('progress_cumulative', 5, 2)->default(0);
                $table->timestamps();
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('item_realizations');
        Schema::dropIfExists('weekly_realizations');
        Schema::dropIfExists('project_schedules');
        
        Schema::table('rab_items', function (Blueprint $table) {
            $table->dropColumn(['weight', 'start_week', 'end_week']);
        });
    }
};