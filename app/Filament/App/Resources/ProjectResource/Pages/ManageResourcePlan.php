<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\WeeklyResourcePlan;
use App\Services\ResourcePlanningService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ManageResourcePlan extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithRecord;
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.app.resources.project-resource.pages.manage-resource-plan';

    protected static ?string $title = 'Rencana Belanja (Resource Plan)';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WeeklyResourcePlan::query()
                    ->where('project_id', $this->record->id)
            )
            ->columns([
                // Kolom Minggu
                TextColumn::make('week')
                    ->label('Minggu Ke')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => "Minggu $state"),

                // Nama Resource
                TextColumn::make('resource.name')
                    ->label('Nama Bahan/Upah')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Satuan
                TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter(),

                // System Qty (Read Only)
                TextColumn::make('system_qty')
                    ->label('Sistem (Calc)')
                    ->numeric(4) // 4 Desimal presisi
                    ->alignRight()
                    ->color('primary')
                    ->description('Rekomendasi'),

                // Adjusted Qty (Editable Inline)
                TextInputColumn::make('adjusted_qty')
                    ->label('Plan Order (Final)')
                    ->type('number')
                    ->step(0.0001) // Presisi tinggi
                    ->alignRight()
                    ->placeholder(fn ($record) => number_format($record->system_qty, 4))
                    ->tooltip('Isi angka ini untuk override rekomendasi sistem'),
            ])
            ->defaultSort('week', 'asc')
            ->filters([
                // Filter Minggu
                SelectFilter::make('week')
                    ->label('Pilih Minggu')
                    ->options(function () {
                        // Ambil list minggu yang tersedia
                        $weeks = WeeklyResourcePlan::where('project_id', $this->record->id)
                            ->distinct()
                            ->pluck('week')
                            ->sort();
                        
                        $options = [];
                        foreach ($weeks as $w) {
                            $options[$w] = "Minggu ke-$w";
                        }
                        return $options;
                    })
                    ->default(1), // Default tampilkan minggu 1 agar tidak berat load semua
            ])
            ->headerActions([
                // Tombol Hitung Ulang
                Tables\Actions\Action::make('recalculate')
                    ->label('Hitung Ulang System Plan')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        try {
                            $service = new ResourcePlanningService();
                            $service->generateWeeklyPlan($this->record);
                            
                            Notification::make()
                                ->title('Kalkulasi Selesai')
                                ->body('Data kebutuhan resource telah diperbarui berdasarkan jadwal.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}