<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectSchedule;
use App\Models\WeeklyRealization;

class CurveCalculatorService
{
    public function calculateItemWeights(Project $project): void
    {
        $items = $project->rabItems;
        $totalContract = $items->sum('total_price'); // Hitung dari items langsung biar konsisten

        if ($totalContract <= 0) return;

        $currentSumWeight = 0;
        $count = $items->count();

        // Loop semua item
        foreach ($items as $index => $item) {
            // Jika ini adalah ITEM TERAKHIR
            if ($index === $count - 1) {
                // Bobotnya adalah sisa (100 - total sebelumnya)
                // Ini memaksa total pasti 100.00
                $weight = 100 - $currentSumWeight;
            } else {
                // Item biasa: hitung normal
                $weight = ($item->total_price / $totalContract) * 100;
                // Bulatkan ke 4 desimal biar rapi tapi tetap akurat
                $weight = round($weight, 4);
            }

            // Simpan weight ke DB
            $item->update(['weight' => $weight]);
            
            // Tambahkan ke akumulator
            $currentSumWeight += $weight;
        }
        
        // Update contract value di project header sekalian
        $project->update(['contract_value' => $totalContract]);
    }

    /**
     * Mengembalikan Data Lengkap untuk Tabel & Chart
     */
    public function getScurveData(Project $project): array
    {
        // 1. Ambil Data Jadwal (Plan)
        $schedules = ProjectSchedule::where('project_id', $project->id)
            ->orderBy('week')
            ->get();

        // 2. Ambil Data Realisasi (Actual)
        $realizations = WeeklyRealization::where('project_id', $project->id)
            ->where('status', 'submitted') // Atau 'approved', sesuaikan workflow
            ->orderBy('week')
            ->get();

        // 3. Tentukan Durasi Maksimal (Plan vs Actual)
        $maxWeekPlan = $schedules->max('week') ?? 0;
        $maxWeekReal = $realizations->max('week') ?? 0;
        $maxWeek = max($maxWeekPlan, $maxWeekReal);

        $tableData = [];
        $cumPlan = 0;
        $cumActual = 0;

        // Loop dari Minggu 1 s/d Akhir
        for ($i = 1; $i <= $maxWeek; $i++) {
            // --- Hitung Plan ---
            $sched = $schedules->firstWhere('week', $i);
            $planWeekly = $sched ? (float) $sched->progress_plan : 0;
            $cumPlan += $planWeekly;

            // --- Hitung Actual ---
            // Cek apakah minggu ini sudah ada realisasinya
            $real = $realizations->firstWhere('week', $i);
            
            $actualWeekly = null;
            $currentActualCum = null;
            $deviation = null;

            // Jika minggu ini <= minggu terakhir realisasi, hitung actualnya
            if ($i <= $maxWeekReal) {
                // Asumsi: realized_progress di DB adalah progress minggu itu (parsial)
                // Jika DB nyimpan kumulatif, logic ini perlu disesuaikan.
                $val = $real ? (float) $real->realized_progress : 0;
                $cumActual += $val;
                
                $actualWeekly = $val;
                $currentActualCum = $cumActual;

                // Hitung Deviasi (Actual - Plan)
                $deviation = $currentActualCum - $cumPlan;
            }

            // Masukkan ke array Tabel
            $tableData[] = [
                'week' => $i,
                'plan_weekly' => round($planWeekly, 2),
                'plan_cumulative' => round($cumPlan, 2),
                
                // Jika belum ada realisasi, biarkan null agar di tabel muncul strip (-)
                'actual_weekly' => $actualWeekly !== null ? round($actualWeekly, 2) : null,
                'actual_cumulative' => $currentActualCum !== null ? round($currentActualCum, 2) : null,
                'deviation' => $deviation !== null ? round($deviation, 2) : null,
            ];
        }

        return $tableData;
    }
}