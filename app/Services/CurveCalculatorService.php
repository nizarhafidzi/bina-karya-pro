<?php

namespace App\Services;

use App\Models\Project;
use App\Models\RabItem;
use App\Models\ProjectSchedule;
use App\Models\WeeklyRealization;

class CurveCalculatorService
{
    /**
     * Hitung ulang Bobot (%) setiap item terhadap Nilai Kontrak Total.
     * Wajib dipanggil setiap kali RAB berubah.
     */
    public function calculateItemWeights(Project $project): void
    {
        $totalContract = $project->contract_value;

        if ($totalContract <= 0) return;

        // Ambil via shortcut relationship (HasManyThrough)
        $items = $project->rabItems;

        foreach ($items as $item) {
            // Rumus: (Harga Total Item / Nilai Kontrak Proyek) * 100
            $weight = ($item->total_price / $totalContract) * 100;
            
            $item->update(['weight' => $weight]);
        }
    }

    /**
     * Menghasilkan Data Array Kurva S (Plan vs Actual) lengkap.
     * Output format siap pakai untuk Tabel & Chart.
     */
    public function getScurveData(Project $project): array
    {
        // 1. Tentukan Rentang Waktu
        // Asumsi: Max week diambil dari schedule terakhir atau realization terakhir
        $maxWeekSchedule = ProjectSchedule::whereHas('rabItem.wbs', fn($q) => $q->where('project_id', $project->id))->max('week') ?? 0;
        $maxWeekRealization = WeeklyRealization::where('project_id', $project->id)->max('week') ?? 0;
        $totalWeeks = max($maxWeekSchedule, $maxWeekRealization);

        if ($totalWeeks == 0) return [];

        $data = [];
        $cumulativePlan = 0;
        $cumulativeActual = 0;

        for ($week = 1; $week <= $totalWeeks; $week++) {
            
            // --- A. CALCULATE PLAN (RENCANA) ---
            // Ambil semua schedule di minggu ini untuk proyek ini
            $schedules = ProjectSchedule::where('week', $week)
                ->whereHas('rabItem.wbs', fn($q) => $q->where('project_id', $project->id))
                ->with('rabItem')
                ->get();

            $weeklyPlanWeight = 0;
            foreach ($schedules as $sched) {
                // Kontribusi Item ke Progress Proyek = (Bobot Item * Progress Rencana Item) / 100
                $contribution = ($sched->rabItem->weight * $sched->progress_plan) / 100;
                $weeklyPlanWeight += $contribution;
            }

            $cumulativePlan += $weeklyPlanWeight;

            // --- B. CALCULATE ACTUAL (REALISASI) ---
            // Cari realization header untuk minggu ini
            $realization = WeeklyRealization::where('project_id', $project->id)
                ->where('week', $week)
                ->with(['itemRealizations.rabItem'])
                ->first();

            $weeklyActualWeight = 0;
            $hasActual = false;

            if ($realization) {
                $hasActual = true;
                foreach ($realization->itemRealizations as $itemReal) {
                    // Logic: Kita hitung progress MINGGU INI saja (Delta)
                    // Jika data progress_cumulative yg disimpan, kita butuh delta dari minggu lalu.
                    // TAPI untuk simplifikasi Engine ini, kita asumsikan item_realization menyimpan
                    // 'progress_this_week' (Progress yg dicapai HANYA di minggu ini).
                    
                    $contribution = ($itemReal->rabItem->weight * $itemReal->progress_this_week) / 100;
                    $weeklyActualWeight += $contribution;
                }
                $cumulativeActual += $weeklyActualWeight;
            }

            // --- C. COMPILE DATA ---
            $data[] = [
                'week' => $week,
                'plan_weekly' => round($weeklyPlanWeight, 2),
                'plan_cumulative' => round($cumulativePlan, 2),
                'actual_weekly' => $hasActual ? round($weeklyActualWeight, 2) : null,
                'actual_cumulative' => $hasActual ? round($cumulativeActual, 2) : null,
                'deviation' => $hasActual ? round($cumulativeActual - $cumulativePlan, 2) : null,
            ];
        }

        return $data;
    }
}