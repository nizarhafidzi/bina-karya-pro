<?php

namespace Database\Seeders;

use App\Models\CashFlowActual;
use App\Models\CashFlowPlan;
use App\Models\Project;
use Illuminate\Database\Seeder;

class CashFlowSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::first();
        
        if (!$project) {
            $this->command->error('Tidak ada project ditemukan. Jalankan project seeder dulu.');
            return;
        }

        $teamId = $project->team_id;

        // 1. RENCANA (PLAN)
        CashFlowPlan::create([
            'team_id' => $teamId, 
            'project_id' => $project->id,
            'type' => 'in', 
            'category' => 'termin', // <--- Tambahan
            'amount' => 100000000, 
            'date' => now()->subDays(30), 
            'description' => 'Rencana Termin 1'
        ]);
        
        CashFlowPlan::create([
            'team_id' => $teamId, 
            'project_id' => $project->id,
            'type' => 'out', 
            'category' => 'material', // <--- Tambahan
            'amount' => 50000000, 
            'date' => now()->subDays(25), 
            'description' => 'Estimasi Material Awal'
        ]);

        // 2. REALISASI (ACTUAL)
        CashFlowActual::create([
            'team_id' => $teamId, 
            'project_id' => $project->id,
            'type' => 'in', 
            'category' => 'termin', // <--- Tambahan
            'amount' => 95000000, 
            'date' => now()->subDays(29), 
            'description' => 'Termin 1 Masuk Bank'
        ]);
        
        CashFlowActual::create([
            'team_id' => $teamId, 
            'project_id' => $project->id,
            'type' => 'out', 
            'category' => 'material', // <--- Tambahan
            'amount' => 55000000, 
            'date' => now()->subDays(24), 
            'description' => 'Bayar Toko Besi'
        ]);

        $this->command->info("Data Dummy Cash Flow berhasil dibuat.");
    }
}