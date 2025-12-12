<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\WeeklyRealization;
use App\Services\CurveCalculatorService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable; // Interface Wajib untuk Table di Page
use Filament\Tables\Concerns\InteractsWithTable; // Trait Wajib

class ProjectProgressPage extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable; // Load Trait Table

    protected static string $resource = ProjectResource::class;
    protected static string $view = 'filament.app.resources.project-resource.pages.project-progress-page';
    protected static ?string $title = 'Monitoring Progress (Kurva S)';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    // Data untuk Chart (dikirim ke View)
    public $curveData = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // Load Data Chart
        $service = new CurveCalculatorService();
        $service->calculateItemWeights($this->record);
        $this->curveData = $service->getScurveData($this->record);
    }

    /**
     * Tabel Collapsible / Drill-down Progress Mingguan
     */
    public function table(Table $table): Table
    {
        return $table
            // Query: Ambil Realisasi Mingguan milik Project ini
            ->query(
                WeeklyRealization::query()
                    ->where('project_id', $this->record->id)
                    ->orderBy('week', 'asc')
            )
            ->heading('Riwayat Realisasi Mingguan')
            ->description('Klik "Rincian Item" untuk melihat detail pekerjaan yang dilakukan.')
            ->columns([
                Tables\Columns\TextColumn::make('week')
                    ->label('Minggu Ke')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('realized_progress')
                    ->label('Progress Mingguan')
                    ->suffix('%')
                    ->weight('bold')
                    ->color('success'),

                // Jika kolom cumulative belum ada di DB, kita hitung on-the-fly di view/chart saja
                // atau tampilkan tanggal periode
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Periode')
                    ->date('d M')
                    ->description(fn ($record) => 's/d ' . \Carbon\Carbon::parse($record->end_date)->format('d M Y')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),
            ])
            ->actions([
                // ACTION DRILL-DOWN (MODAL)
                Tables\Actions\Action::make('view_items')
                    ->label('Rincian Item')
                    ->icon('heroicon-m-list-bullet')
                    ->modalHeading(fn ($record) => "Rincian Progress Minggu ke-{$record->week}")
                    ->modalSubmitAction(false) // View only
                    ->modalContent(function (WeeklyRealization $record) {
                        // Render View Blade Custom untuk Tabel Rincian
                        // Kita pass data items ke view tersebut
                        return view('filament.app.resources.project-resource.components.item-realization-table', [
                            'items' => $record->itemRealizations()->with('rabItem.ahsMaster')->get(),
                        ]);
                    }),
            ]);
    }
}