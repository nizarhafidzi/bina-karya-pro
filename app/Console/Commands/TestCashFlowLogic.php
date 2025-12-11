<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\CashFlowService;
use Illuminate\Console\Command;

class TestCashFlowLogic extends Command
{
    protected $signature = 'test:cashflow';
    protected $description = 'Verifikasi manual logika perhitungan Cash Flow Service';

    public function handle()
    {
        $project = Project::first();
        if (!$project) {
            $this->error('Project tidak ditemukan.');
            return;
        }

        $this->info("Menguji Cash Flow untuk Project: {$project->name}");
        
        // Panggil Service
        $service = new CashFlowService();
        $data = $service->generateCashFlowData($project);

        // Tampilkan Tabel di Terminal
        $headers = ['Tanggal', 'Jenis', 'Sumber', 'Deskripsi', 'Nominal', 'Saldo Plan', 'Saldo Actual'];
        
        $rows = $data->map(function ($row) {
            return [
                $row['date'],
                $row['flow_type'],
                $row['source'],
                $row['description'],
                number_format($row['amount']),
                number_format($row['balance_plan']),   // <--- Ini yang mau kita cek
                number_format($row['balance_actual']), // <--- Ini juga
            ];
        });

        $this->table($headers, $rows);
        
        $this->info("Testing Selesai. Silakan cek kolom Saldo apakah perhitungannya kumulatif dengan benar.");
    }
}