<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WeeklyResourcePlan;
use App\Models\ProjectSchedule;
use Illuminate\Support\Facades\DB;

class ResourcePlanningService
{
    public function generateWeeklyPlan(Project $project): void
    {
        DB::beginTransaction();
        try {
            // Reset system plan
            WeeklyResourcePlan::where('project_id', $project->id)
                ->whereNull('adjusted_qty')
                ->delete();

            $plans = [];

            // Ambil jadwal lengkap dengan struktur AHS
            $schedules = ProjectSchedule::where('project_id', $project->id)
                ->with(['rabItem.ahsMaster.coefficients.resource'])
                ->get();

            foreach ($schedules as $schedule) {
                $rabItem = $schedule->rabItem;
                
                // Validasi data
                if (!$rabItem || $rabItem->qty <= 0 || !$rabItem->ahsMaster) {
                    continue;
                }

                $weeklyProgressRatio = $schedule->progress_plan / 100; // e.g. 0.5
                if ($weeklyProgressRatio <= 0) continue;

                // Loop Resource di AHS
                foreach ($rabItem->ahsMaster->coefficients as $coef) {
                    $coefficient = $coef->coefficient ?? 0;
                    if ($coefficient <= 0) continue;

                    // RUMUS: (Vol Item * Koefisien) * % Mingguan
                    $totalResource = $rabItem->qty * $coefficient;
                    $weeklyQty = $totalResource * $weeklyProgressRatio;

                    if ($weeklyQty > 0) {
                        $week = $schedule->week;
                        $resId = $coef->resource_id;
                        $unit = $coef->resource->unit ?? 'ls';

                        if (!isset($plans[$week][$resId])) {
                            $plans[$week][$resId] = ['qty' => 0, 'unit' => $unit];
                        }
                        $plans[$week][$resId]['qty'] += $weeklyQty;
                    }
                }
            }

            // Simpan ke DB
            foreach ($plans as $week => $resources) {
                foreach ($resources as $resId => $data) {
                    WeeklyResourcePlan::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'team_id' => $project->team_id,
                            'week' => $week,
                            'resource_id' => $resId,
                        ],
                        [
                            'system_qty' => $data['qty'],
                            'unit' => $data['unit'],
                        ]
                    );
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}