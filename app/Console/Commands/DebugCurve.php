<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\ProjectSchedule;
use Illuminate\Support\Facades\DB;

class DebugCurve extends Command
{
    protected $signature = 'debug:curve';
    protected $description = 'Investigasi Anomali Hitungan Kurva S (Mencari selisih 0.04%)';

    public function handle()
    {
        $project = Project::first();
        if (!$project) {
            $this->error('Project tidak ditemukan.');
            return;
        }

        $this->info("=== INVESTIGASI PROYEK: {$project->name} ===");
        $this->info("Nilai Kontrak di DB Project: " . number_format($project->contract_value, 2));

        // --- CEK 1: KONSISTENSI TOTAL HARGA RAB ---
        $items = RabItem::whereHas('wbs', fn($q) => $q->where('project_id', $project->id))->get();
        $realTotal = $items->sum('total_price');
        
        $this->line("");
        $this->info("--- 1. CEK KONSISTENSI RAB ---");
        $this->line("Total Sum Item RAB: " . number_format($realTotal, 2));
        
        if (abs($realTotal - $project->contract_value) > 1) {
            $this->error("ALARM: Nilai Kontrak di Table Project BEDA dengan Sum Item RAB!");
            $this->error("Selisih: " . ($realTotal - $project->contract_value));
            $this->comment("Solusi: Masalah ini menyebabkan bobot kacau.");
        } else {
            $this->info("OK: Nilai Kontrak Sinkron.");
        }

        // --- CEK 2: KONSISTENSI BOBOT (WEIGHTS) ---
        $this->line("");
        $this->info("--- 2. CEK TOTAL BOBOT (WEIGHTS) ---");
        $totalWeight = $items->sum('weight');
        $this->line("Total Bobot di Database: {$totalWeight}%");

        if (abs($totalWeight - 100) > 0.01) {
            $this->error("ALARM: Total Bobot TIDAK 100%!");
            $this->error("Selisih: " . ($totalWeight - 100) . "%");
            $this->comment("Penyebab: Ini biang kerok utamanya. Bobot item belum dinormalisasi.");
            
            // Tampilkan item dengan bobot aneh
            $this->table(
                ['Item', 'Harga', 'Bobot DB (%)'],
                $items->map(fn($i) => [
                    substr($i->ahsMaster->name ?? 'Item #'.$i->id, 0, 20),
                    number_format($i->total_price),
                    $i->weight
                ])
            );
        } else {
            $this->info("OK: Total Bobot Sempurna (100%).");
        }

        // --- CEK 3: KONSISTENSI JADWAL (SCHEDULES) ---
        $this->line("");
        $this->info("--- 3. CEK PROGRESS JADWAL PER ITEM ---");
        
        $schedules = ProjectSchedule::where('project_id', $project->id)->get();
        $anomalies = [];

        foreach ($items as $item) {
            $itemSchedules = $schedules->where('rab_item_id', $item->id);
            $totalProgressItem = $itemSchedules->sum('progress_plan'); // Harusnya 100

            if (abs($totalProgressItem - 100) > 0.05) {
                $anomalies[] = [
                    'item' => substr($item->ahsMaster->name ?? 'Item #'.$item->id, 0, 20),
                    'total_prog' => $totalProgressItem
                ];
            }
        }

        if (count($anomalies) > 0) {
            $this->error("ALARM: Ada Item yang jadwalnya TIDAK 100% selesai!");
            $this->table(['Item', 'Total Jadwal (%)'], $anomalies);
        } else {
            $this->info("OK: Semua Item dijadwalkan selesai 100%.");
        }

        // --- CEK 4: SIMULASI HITUNGAN AKHIR (The Grand Total) ---
        $this->line("");
        $this->info("--- 4. SIMULASI HITUNGAN AKHIR ---");
        
        $grandTotal = 0;
        foreach ($schedules as $sched) {
            $item = $items->firstWhere('id', $sched->rab_item_id);
            if (!$item) continue;

            // Rumus Kurva S: (Fisik * Bobot) / 100
            $contribution = ($sched->progress_plan * $item->weight) / 100;
            $grandTotal += $contribution;
        }

        $this->line("Grand Total Kurva S (Hitung Manual): {$grandTotal}%");

        if ($grandTotal > 100.01) {
            $this->error("KESIMPULAN: Masalahnya ada di AKUMULASI desimal.");
            $this->comment("Saran: Jalankan fitur 'Force Normalization' di CurveCalculatorService.");
        } elseif ($grandTotal < 99.99) {
            $this->error("KESIMPULAN: Data kurang (Under 100%).");
        } else {
            $this->info("KESIMPULAN: Data Sehat. Jika di UI masih salah, berarti masalah di View/Logic Tampilan.");
        }
    }
}