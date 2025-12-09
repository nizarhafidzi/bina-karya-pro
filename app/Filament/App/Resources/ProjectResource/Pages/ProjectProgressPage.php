<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\Project;
use App\Services\CurveCalculatorService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ProjectProgressPage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;
    protected static string $view = 'filament.app.resources.project-resource.pages.project-progress-page';
    protected static ?string $title = 'Monitoring Kurva S';

    // Data untuk Tabel
    public array $curveData = [];
    
    // Data untuk Chart (Tambahkan variabel ini)
    public array $chartDataset = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        $service = new CurveCalculatorService();
        $service->calculateItemWeights($this->record);
        
        // 1. Ambil Data Tabel (Format Baru)
        $this->curveData = $service->getScurveData($this->record);

        // 2. Konversi Data Tabel menjadi Data Chart (Agar Grafik Muncul)
        $this->prepareChartData();
    }

    private function prepareChartData(): void
    {
        $labels = [];
        $planData = [];
        $actualData = [];

        foreach ($this->curveData as $row) {
            $labels[] = 'Mg ' . $row['week'];
            $planData[] = $row['plan_cumulative'];
            
            // Hanya masukkan data actual jika tidak null (agar grafik tidak turun ke 0 di masa depan)
            if ($row['actual_cumulative'] !== null) {
                $actualData[] = $row['actual_cumulative'];
            }
        }

        $this->chartDataset = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Rencana (Plan)',
                    'data' => $planData,
                    'borderColor' => '#3b82f6',
                    'fill' => false,
                ],
                [
                    'label' => 'Realisasi (Actual)',
                    'data' => $actualData,
                    'borderColor' => '#22c55e',
                    'fill' => false,
                ],
            ],
        ];
    }
}