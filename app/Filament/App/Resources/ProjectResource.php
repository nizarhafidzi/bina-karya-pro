<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ProjectResource\Pages;
use App\Filament\App\Resources\ProjectResource\RelationManagers\RabItemsRelationManager;
use App\Filament\App\Resources\ProjectResource\Pages\ProjectProgressPage;
use App\Filament\App\Resources\ProjectResource\Pages\ProjectSchedulePage;
use App\Filament\App\Resources\ProjectResource\Pages\WeeklyProgressInputPage;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\RabCalculatorService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;


class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Project Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Proyek')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Nama Proyek'),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Nomor Kontrak/SPK'),
                        Forms\Components\Select::make('region_id')
                            ->relationship('region', 'name')
                            ->required()
                            ->label('Wilayah Harga (Region)')
                            ->helperText('Menentukan standar harga material yang digunakan.'),
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date'),
                        Forms\Components\TextInput::make('contract_value')
                            ->label('Nilai Kontrak')
                            ->disabled() // Otomatis terhitung dari RAB
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->dehydrated(false), // Jangan submit jika disabled, tapi di backend kita update
                        Forms\Components\TextInput::make('contract_value')
                            ->label('Nilai Kontrak (Total RAB)')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            
                            // 1. Agar user tidak bisa edit manual (harus hasil hitungan)
                            ->readOnly() 
                            
                            // 2. Agar data tetap tersimpan ke DB meskipun readOnly
                            ->dehydrated() 
                            
                            // 3. TOMBOL MANUAL UPDATE (SOLUSI ANDA)
                            ->suffixAction(
                                Action::make('recalculate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('warning')
                                    ->tooltip('Klik untuk hitung ulang Total RAB terkini')
                                    ->action(function ($record, Forms\Set $set) {
                                        // Jika record belum tersimpan (proyek baru), tidak bisa hitung
                                        if (!$record) return;

                                        // A. Jalankan Service Kalkulator
                                        (new RabCalculatorService())->calculateProject($record);
                                        
                                        // B. Ambil data terbaru dari DB
                                        $newValue = $record->fresh()->contract_value;

                                        // C. Update tampilan di Form seketika
                                        $set('contract_value', $newValue);

                                        // D. Beri notifikasi sukses
                                        Notification::make()
                                            ->title('Nilai Kontrak Diperbarui')
                                            ->body('Total: Rp ' . number_format($newValue, 0, ',', '.'))
                                            ->success()
                                            ->send();
                                    })
                            ),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('region.name')->badge(),
                Tables\Columns\TextColumn::make('contract_value')->money('IDR')->label('Nilai RAB'),
                Tables\Columns\TextColumn::make('start_date')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Group Action untuk Menu Teknis
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('schedule')
                        ->label('Planning Jadwal')
                        ->icon('heroicon-o-calendar')
                        ->url(fn (Project $record) => ProjectSchedulePage::getUrl(['record' => $record])),
                    
                    Tables\Actions\Action::make('opname')
                        ->label('Input Progress')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->url(fn (Project $record) => WeeklyProgressInputPage::getUrl(['record' => $record])),
                        
                    Tables\Actions\Action::make('curve')
                        ->label('Monitoring Kurva S')
                        ->icon('heroicon-o-presentation-chart-line')
                        ->url(fn (Project $record) => ProjectProgressPage::getUrl(['record' => $record])),
                ])
                ->label('Menu Teknis')
                ->icon('heroicon-m-cog')
                ->color('info'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RabItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'progress' => ProjectProgressPage::route('/{record}/progress'),
            'schedule' => ProjectSchedulePage::route('/{record}/schedule'),
            'opname'   => WeeklyProgressInputPage::route('/{record}/opname'),
        ];
    }
}