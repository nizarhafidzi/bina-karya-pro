<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\Project;
use App\Services\CurveCalculatorService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ProjectProgressPage extends Page
{
    use InteractsWithRecord; // Agar bisa akses $this->record (Project)

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.app.resources.project-resource.pages.project-progress-page';

    protected static ?string $title = 'Monitoring Kurva S';

    public $curveData = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // Panggil Engine Hitung
        $service = new CurveCalculatorService();
        
        // 1. Pastikan Bobot Item terupdate dulu
        $service->calculateItemWeights($this->record);
        
        // 2. Ambil Data Kurva
        $this->curveData = $service->getScurveData($this->record);
    }
}