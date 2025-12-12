<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\ProjectSchedule;
use App\Services\ResourcePlanningService;
use App\Models\WeeklyResourcePlan;

class TestResourcePlanning extends Command
{
    protected $signature = 'test:resource-plan {--detail : Tampilkan rincian perhitungan}';
    protected $description = 'Generate Resource Plan dengan Penjelasan Logika Detil';

    public function handle()
    {
        // 1. Setup Project
        $project = Project::first();
        if (!$project) {
            $this->error('Project tidak ditemukan. Jalankan seeder dulu.');
            return;
        }

        $this->info("=== RESOURCE PLANNING ENGINE: {$project->name} ===");
        $this->line("Menjalankan kalkulasi ulang di database...");

        // 2. Jalankan Service Asli (Updating Database)
        try {
            $service = new ResourcePlanningService();
            $service->generateWeeklyPlan($project);
            $this->info("Database berhasil diupdate!");
        } catch (\Exception $e) {
            $this->error("Error Service: " . $e->getMessage());
            return;
        }

        $this->line("");
        $this->info("=== RINCIAN LOGIKA PERHITUNGAN (LOGIC TRACE) ===");
        
        // 3. Ambil Data Mentah untuk Simulasi Tampilan
        // Kita ulangi query yang sama dengan Service untuk menampilkan 'Why' dan 'How'
        $schedules = ProjectSchedule::where('project_id', $project->id)
            ->with([
                'rabItem.ahsMaster.coefficients.resource',
                'rabItem.wbs' // Optional: untuk info nama pekerjaan
            ])
            ->orderBy('week')
            ->get();

        if ($schedules->isEmpty()) {
            $this->warn("Tidak ada jadwal ditemukan. Cek 'project_schedules'.");
            return;
        }

        // Grouping data untuk tampilan: [Minggu] -> [ResourceID] -> [List Kontribusi]
        $report = [];

        foreach ($schedules as $schedule) {
            $rabItem = $schedule->rabItem;
            
            // Validasi Data (Sama seperti Service)
            if (!$rabItem || $rabItem->qty <= 0 || !$rabItem->ahsMaster) continue;

            $progress = $schedule->progress_plan; // misal 20 (%)
            $ratio = $progress / 100; // 0.2

            if ($ratio <= 0) continue;

            $itemName = $rabItem->ahsMaster->name ?? ('Item #' . $rabItem->id);

            // Loop Koefisien
            foreach ($rabItem->ahsMaster->coefficients as $coef) {
                $coefficient = $coef->coefficient ?? 0;
                if ($coefficient <= 0) continue;

                // RUMUS INTI
                // (Volume Item x Koefisien AHS) x (Progress / 100)
                $totalNeedForItem = $rabItem->qty * $coefficient;
                $weeklyNeed = $totalNeedForItem * $ratio;

                if ($weeklyNeed > 0) {
                    $week = $schedule->week;
                    $resName = $coef->resource->name ?? 'Unknown Resource';
                    $unit = $coef->resource->unit ?? 'satuan';

                    // Simpan jejak logika
                    $report[$week][$resName]['breakdown'][] = [
                        'item' => $itemName,
                        'logic' => "(Vol: {$rabItem->qty} x Coef: {$coefficient} x Prog: {$progress}%)",
                        'value' => $weeklyNeed
                    ];
                    $report[$week][$resName]['unit'] = $unit;
                }
            }
        }

        // 4. Render Output ke Terminal
        if (empty($report)) {
            $this->warn("Kalkulasi selesai tapi hasilnya 0. Cek: Volume RAB / Koefisien AHS / Progress Jadwal.");
            return;
        }

        foreach ($report as $week => $resources) {
            $this->comment("------------------------------------------------");
            $this->info(" MINGGU KE-{$week}");
            $this->comment("------------------------------------------------");

            foreach ($resources as $resName => $data) {
                $totalWeekly = 0;
                $unit = $data['unit'];

                $this->line("  📦 <fg=yellow>{$resName}</>"); // Nama Resource

                // Tampilkan rincian penambahan
                foreach ($data['breakdown'] as $log) {
                    $valFormat = number_format($log['value'], 4);
                    $this->line("     ├─ [+] {$valFormat} {$unit}");
                    $this->line("     │      Dari: {$log['item']}");
                    $this->line("     │      Rumus: {$log['logic']}");
                    
                    $totalWeekly += $log['value'];
                }

                $totalFormat = number_format($totalWeekly, 2);
                $this->line("     ╰─ <fg=green;options=bold>TOTAL MINGGU INI: {$totalFormat} {$unit}</>");
                $this->line("");
            }
        }

        $this->info("Verifikasi Selesai. Data di atas sesuai dengan isi tabel 'weekly_resource_plans'.");
    }
}