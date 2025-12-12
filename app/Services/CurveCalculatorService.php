<?php

namespace App\Services;

use App\Models\Project;
use App\Models\RabItem;
use App\Models\ProjectSchedule;
use App\Models\WeeklyRealization;
use Illuminate\Support\Facades\DB;

class CurveCalculatorService
{
    /**
     * Hitung ulang Bobot setiap item RAB agar totalnya PAS 100%.
     * Langkah:
     * 1. Hitung Total Real dari item-item RAB.
     * 2. Update Nilai Kontrak Project (agar sinkron).
     * 3. Bagi proporsi bobot.
     * 4. Normalisasi selisih koma (Force 100%).
     */
    public function calculateItemWeights(Project $project): void
    {
        // 1. Ambil semua item RAB milik proyek ini
        $items = RabItem::whereHas('wbs', fn($q) => $q->where('project_id', $project->id))->get();

        if ($items->isEmpty()) return;

        // 2. Hitung Total Real (Sum Total Price Items)
        // Kita tidak percaya 'contract_value' di tabel project, kita hitung ulang dari rincian.
        $realTotal = $items->sum('total_price');

        if ($realTotal <= 0) return;

        // 3. Update Contract Value di Proyek agar sinkron
        $project->update(['contract_value' => $realTotal]);

        // 4. Hitung Bobot Mentah
        // Kita simpan ID dan Bobot sementara di array untuk dinormalisasi
        $tempWeights = [];
        $totalWeight = 0;

        foreach ($items as $item) {
            // Rumus: (Harga Item / Total Proyek) * 100
            $weight = ($item->total_price / $realTotal) * 100;
            
            // Simpan sementara (tanpa round dulu biar presisi)
            $tempWeights[$item->id] = $weight;
            $totalWeight += $weight;
        }

        // 5. Normalisasi & Rounding (Sapu Jagat Bobot)
        // Agar total bobot di database DIJAMIN 100.00
        
        $finalWeights = [];
        foreach ($tempWeights as $id => $w) {
            $finalWeights[$id] = round($w, 2); // Bulatkan 2 desimal untuk DB
        }

        // Cek selisih
        $diff = 100.00 - array_sum($finalWeights);

        // Tempel selisih ke item dengan harga TERBESAR (biar dampaknya paling minim)
        // atau item terakhir
        if (abs($diff) > 0.00001 && count($finalWeights) > 0) {
            // Kita cari item terakhir saja untuk simplifikasi
            $lastId = array_key_last($finalWeights);
            $finalWeights[$lastId] += $diff;
        }

        // 6. Simpan Bobot Final ke Database
        foreach ($finalWeights as $itemId => $weight) {
            RabItem::where('id', $itemId)->update(['weight' => $weight]);
        }
    }

    public function getScurveData(Project $project): array
    {
        // ... (Bagian ini SAMA SEPERTI SEBELUMNYA, tidak perlu diubah) ...
        
        $schedules = ProjectSchedule::where('project_id', $project->id)->with('rabItem')->get();
        
        $realizations = WeeklyRealization::where('project_id', $project->id)
            ->where('status', 'submitted')
            ->orderBy('week')
            ->get();

        $maxWeekPlan = $schedules->max('week') ?? 0;
        $maxWeekReal = $realizations->max('week') ?? 0;
        $maxWeek = max($maxWeekPlan, $maxWeekReal);

        $tableData = [];
        $cumPlan = 0;
        $cumActual = 0;

        for ($i = 1; $i <= $maxWeek; $i++) {
            // A. Plan
            $weeklySchedules = $schedules->where('week', $i);
            $planWeekly = 0;
            
            foreach ($weeklySchedules as $sched) {
                if (!$sched->rabItem) continue;
                $weight = (float) $sched->rabItem->weight;
                $physical = (float) $sched->progress_plan;
                $planWeekly += ($physical * $weight) / 100;
            }
            $cumPlan += $planWeekly;

            // B. Actual
            $real = $realizations->firstWhere('week', $i);
            $actualWeekly = null;
            $currentActualCum = null;
            $deviation = null;

            if ($i <= $maxWeekReal) {
                $val = $real ? (float) $real->realized_progress : 0;
                $cumActual += $val;
                $actualWeekly = $val;
                $currentActualCum = $cumActual;
                $deviation = $currentActualCum - $cumPlan;
            }

            $tableData[] = [
                'week' => $i,
                'plan_weekly' => round($planWeekly, 2),
                'plan_cumulative' => round($cumPlan, 2),
                'actual_weekly' => $actualWeekly !== null ? round($actualWeekly, 2) : null,
                'actual_cumulative' => $currentActualCum !== null ? round($currentActualCum, 2) : null,
                'deviation' => $deviation !== null ? round($deviation, 2) : null,
            ];
        }

        return $tableData;
    }
}