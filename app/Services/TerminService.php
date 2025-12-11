<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTermin;
use App\Models\WeeklyRealization;

class TerminService
{
    /**
     * Mengecek dan mengupdate status termin berdasarkan progress fisik terkini.
     * Mengubah 'planned' -> 'ready' jika target tercapai.
     */
    public function syncTerminStatus(Project $project): void
    {
        // 1. Ambil TOTAL Progress Fisik Kumulatif (Corrected Logic)
        // Kita menjumlahkan semua progress mingguan yang statusnya 'submitted'
        $cumulativeProgress = WeeklyRealization::where('project_id', $project->id)
            ->where('status', 'submitted')
            ->sum('realized_progress'); // <--- PENTING: Pakai SUM, bukan VALUE/LAST

        // 2. Cek semua Termin yang masih 'planned'
        $termins = $project->termins()->where('status', 'planned')->get();

        foreach ($termins as $termin) {
            // Jika progress kumulatif lapangan >= target termin
            // Kita gunakan bccomp atau round untuk menghindari masalah presisi float
            if (round($cumulativeProgress, 2) >= round($termin->target_progress, 2)) {
                $termin->update([
                    'status' => 'ready' // Trigger notifikasi/badge kuning
                ]);
            }
        }
    }

    /**
     * Hitung Nominal Otomatis berdasarkan Nilai Kontrak
     */
    public function calculateNominal(Project $project, float $percentage): float
    {
        return $project->contract_value * ($percentage / 100);
    }
}