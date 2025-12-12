<?php

namespace Database\Seeders;

use App\Models\DailyLog;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DailyLogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Project & User
        $project = Project::first();
        if (!$project) {
            $this->command->error('Project tidak ditemukan. Jalankan ProjectSeeder dulu.');
            return;
        }

        // Ambil User sembarang sebagai pelapor (Site Manager)
        $siteManager = User::first(); 

        $this->command->info("Membuat Log Harian untuk Proyek: {$project->name}");

        // 2. Buat Data 7 Hari Kebelakang
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Variasi Data Dummy
            $isRainy = rand(0, 10) > 7; // 30% kemungkinan hujan
            $manpower = rand(15, 25);
            
            // Buat Log Harian
            $log = DailyLog::create([
                'project_id' => $project->id,
                'site_manager_id' => $siteManager->id,
                'date' => $date->format('Y-m-d'),
                'weather_am' => $isRainy ? 'Hujan Ringan' : 'Cerah',
                'weather_pm' => $isRainy ? 'Hujan Lebat' : 'Berawan',
                'manpower_total' => $manpower,
                'work_note' => "Pekerjaan hari ke-" . (7 - $i) . ": Lanjutan pemasangan bekisting dan pembesian kolom zona " . rand(1, 4) . ".",
                'material_note' => rand(0, 1) ? "Masuk Semen 50 Sak, Besi D13 100 btg" : null,
                'problem_note' => $isRainy ? "Hujan siang hari menghambat pengecoran selama 2 jam." : null,
            ]);

            // Buat Dummy Images (3 Foto per hari)
            // Note: Kita pakai placeholder image online agar langsung tampil tanpa upload file asli
            for ($img = 1; $img <= 3; $img++) {
                ProjectImage::create([
                    'daily_log_id' => $log->id,
                    // Gunakan Lorem Picsum untuk gambar konstruksi random
                    'path' => "https://picsum.photos/seed/constr_{$log->id}_{$img}/800/600", 
                    'category' => 'progress',
                    'caption' => "Dokumentasi Area " . chr(64 + $img) . " - " . $date->format('d/m')
                ]);
            }
        }

        $this->command->info('Sukses! 7 Log Harian & 21 Foto Dummy telah dibuat.');
    }
}