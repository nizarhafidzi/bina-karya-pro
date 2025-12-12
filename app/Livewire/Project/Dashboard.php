<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Services\CurveCalculatorService;
use Livewire\Component;

class Dashboard extends Component
{
    public Project $project;
    public $chartData = [];
    public $summary = [];

    public function render()
    {
        return view('livewire.project.dashboard')
            ->layout('layouts.project', ['title' => $this->project->name]);
    }

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadDashboardData();
    }

    private function loadDashboardData()
    {
        $service = new CurveCalculatorService();
        
        // 1. Pastikan Bobot Item Terupdate (PENTING)
        $service->calculateItemWeights($this->project);
        
        // 2. Ambil Data Kurva S yang SUDAH BENAR (Plan Weekly x Bobot)
        $rawData = $service->getScurveData($this->project);

        // 3. Transform Data untuk Chart
        $planSeries = [];
        $actualSeries = [];
        $weeks = [];

        $lastActual = 0;
        $lastDeviation = 0;
        
        // Cek apakah ada data actual sama sekali
        $hasActualData = false;

        foreach ($rawData as $row) {
            $weeks[] = 'Mg ' . $row['week'];
            
            // Format angka agar tidak terlalu banyak desimal di JSON
            $planSeries[] = round($row['plan_cumulative'], 2);
            
            // Logic Actual:
            // Jika null, jangan dimasukkan ke array agar grafik putus (tidak turun ke 0)
            // Kecuali minggu pertama (untuk titik awal)
            if ($row['actual_cumulative'] !== null) {
                $val = round($row['actual_cumulative'], 2);
                $actualSeries[] = $val;
                
                // Update tracker terakhir
                $lastActual = $val;
                $lastDeviation = $row['deviation'] !== null ? round($row['deviation'], 2) : 0;
                $hasActualData = true;
            } else {
                // Jika null, push null agar ApexCharts mengerti (Line break)
                // Atau stop push jika ingin garis berhenti
                $actualSeries[] = null; 
            }
        }

        // Clean up trailing nulls di actualSeries agar grafik terlihat berhenti di progress terakhir
        // (Opsional, tergantung selera visual)
        
        $this->chartData = [
            'weeks' => $weeks,
            'plan' => $planSeries,
            'actual' => $actualSeries
        ];

        // 4. Summary Cards
        $this->summary = [
            'contract_value' => $this->project->contract_value,
            'current_progress' => $lastActual,
            'deviation' => $lastDeviation,
            'status' => $this->project->status, // draft, ongoing, etc
            'duration_weeks' => count($rawData)
        ];
    }
}