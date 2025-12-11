<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\Project;
use App\Services\CashFlowService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class CashFlowDashboard extends Page
{
    // Trait ini penting agar halaman tahu dia sedang membuka Project ID berapa
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.app.resources.project-resource.pages.cash-flow-dashboard';

    protected static ?string $title = 'Analisa Keuangan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    // Data untuk View
    public $chartData;
    public $summaryData;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        $service = new CashFlowService();
        
        // Load data untuk Chart
        $this->chartData = $service->getMonthlyChartData($this->record);
        
        // Load data ringkasan (kita reuse yang ada)
        $this->summaryData = $service->getProjectSummary($this->record);
    }
}