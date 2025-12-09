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

    // Tentukan Layout Khusus
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
        
        // 1. Pastikan data ter-update
        $service->calculateItemWeights($this->project);
        
        // 2. Ambil Raw Data (Format Tabel)
        $rawData = $service->getScurveData($this->project);

        // 3. Transform Data untuk ApexCharts (Format Series)
        // Kita butuh array terpisah untuk Plan dan Actual
        $planSeries = [];
        $actualSeries = [];
        $weeks = [];

        $lastActual = 0;
        $lastDeviation = 0;

        foreach ($rawData as $row) {
            $weeks[] = 'Mg ' . $row['week'];
            $planSeries[] = $row['plan_cumulative'];
            
            // Actual hanya diisi jika tidak null (agar grafik tidak turun ke 0 di masa depan)
            if ($row['actual_cumulative'] !== null) {
                $actualSeries[] = $row['actual_cumulative'];
                $lastActual = $row['actual_cumulative'];
                $lastDeviation = $row['deviation'];
            }
        }

        $this->chartData = [
            'weeks' => $weeks,
            'plan' => $planSeries,
            'actual' => $actualSeries
        ];

        // 4. Siapkan Summary Cards
        $this->summary = [
            'contract_value' => $this->project->contract_value,
            'current_progress' => $lastActual,
            'deviation' => $lastDeviation,
            'status' => $this->project->status,
            'duration_weeks' => count($rawData)
        ];
    }
}