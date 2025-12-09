<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectSchedule;
use App\Models\WeeklyRealization;
use App\Models\ItemRealization;
use App\Models\Team;
use App\Services\CurveCalculatorService;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Project yang sudah punya RAB (dari ProjectSeeder Phase 3)
        // Kita asumsikan project pertama milik Tenant "PT Sukses"
        $team = Team::where('slug', 'sukses-jaya')->first();
        if (!$team) {
            $this->command->error("Team Sukses Jaya not found. Run previous seeders first.");
            return;
        }
        
        $project = Project::where('team_id', $team->id)->first();
        if (!$project) {
            $this->command->error("Project not found.");
            return;
        }

        $this->command->info("Simulating Schedule for Project: {$project->name}");

        // 2. Hitung Bobot RAB (Step Awal Engine)
        $service = new CurveCalculatorService();
        $service->calculateItemWeights($project);
        $this->command->info("Item Weights Calculated.");

        // 3. Simulasi Input Rencana Jadwal (Plan)
        // Misal kita punya 1 Item RAB (dari seeder sebelumnya)
        $rabItem = $project->rabItems->first();
        if (!$rabItem) return;

        // Kita rencanakan item ini selesai dalam 2 minggu
        // Minggu 1: 50%, Minggu 2: 50%
        ProjectSchedule::create(['team_id' => $team->id, 'rab_item_id' => $rabItem->id, 'week' => 1, 'progress_plan' => 50]);
        ProjectSchedule::create(['team_id' => $team->id, 'rab_item_id' => $rabItem->id, 'week' => 2, 'progress_plan' => 50]);
        
        $this->command->info("Schedule Plan Created (2 Weeks).");

        // 4. Simulasi Input Realisasi (Actual)
        // Minggu 1: Realisasi cuma 40% (Terlambat)
        $realization1 = WeeklyRealization::create([
            'team_id' => $team->id,
            'project_id' => $project->id,
            'week' => 1,
            'start_date' => now(),
            'end_date' => now()->addDays(6),
        ]);
        
        ItemRealization::create([
            'weekly_realization_id' => $realization1->id,
            'rab_item_id' => $rabItem->id,
            'progress_this_week' => 40,
            'progress_cumulative' => 40
        ]);

        $this->command->info("Week 1 Actual Inputted (40%).");

        // 5. Output Data Kurva S via Service
        $data = $service->getScurveData($project);

        $this->command->table(
            ['Week', 'Plan Weekly', 'Plan Cum', 'Actual Cum', 'Deviation'],
            array_map(fn($row) => [
                $row['week'],
                $row['plan_weekly'].'%',
                $row['plan_cumulative'].'%',
                $row['actual_cumulative'].'%',
                $row['deviation'].'%'
            ], $data)
        );
    }
}