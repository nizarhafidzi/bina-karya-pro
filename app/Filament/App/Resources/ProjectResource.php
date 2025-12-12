<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ProjectResource\Pages;
use App\Filament\App\Resources\ProjectResource\RelationManagers\RabItemsRelationManager;
use App\Filament\App\Resources\ProjectResource\Pages\ProjectProgressPage;
use App\Filament\App\Resources\ProjectResource\Pages\ProjectSchedulePage;
use App\Filament\App\Resources\ProjectResource\Pages\WeeklyProgressInputPage;
use App\Filament\App\Resources\ProjectResource\Pages\CashFlowDashboard;
use App\Filament\App\Resources\ProjectResource\Pages\ManageProjectTermins;
use App\filament\App\Resources\ProjectResource\Pages\ManageResourcePlan;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\RabCalculatorService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;
use Filament\Facades\Filament;


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

                        // --- TAMBAHAN RELASI USER (PERBAIKAN AMBIGUOUS ID) ---
                        Forms\Components\Fieldset::make('Tim Proyek')
                            ->schema([
                                // 1. Pilih Project Owner
                                Forms\Components\Select::make('owner_id')
                                    ->label('Project Owner')
                                    ->options(function () {
                                        $team = Filament::getTenant();
                                        if (!$team) return [];

                                        return $team->members()
                                            ->whereHas('roles', fn ($q) => $q->where('name', 'Project Owner'))
                                            // PERBAIKAN DI SINI: Gunakan 'users.name' dan 'users.id'
                                            ->pluck('users.name', 'users.id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Hanya user dengan role "Project Owner" yang muncul.'),

                                // 2. Pilih Site Managers
                                Forms\Components\Select::make('siteManagers')
                                    ->label('Site Managers')
                                    ->relationship('siteManagers', 'name') 
                                    ->options(function () {
                                        $team = Filament::getTenant();
                                        if (!$team) return [];

                                        return $team->members()
                                            ->whereHas('roles', fn ($q) => $q->where('name', 'Site Manager'))
                                            // PERBAIKAN DI SINI: Gunakan 'users.name' dan 'users.id'
                                            ->pluck('users.name', 'users.id');
                                    })
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Hanya user dengan role "Site Manager" yang muncul.'),
                            ])
                            ->columns(2),
                        // -----------------------------

                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date'),
                        
                        // ... sisa input contract_value dll ...
                        Forms\Components\TextInput::make('contract_value')
                            ->label('Nilai Kontrak (Total RAB)')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->readOnly() 
                            ->dehydrated() 
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('recalculate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('warning')
                                    ->tooltip('Klik untuk hitung ulang Total RAB terkini')
                                    ->action(function ($record, Forms\Set $set) {
                                        if (!$record) return;
                                        (new \App\Services\RabCalculatorService())->calculateProject($record);
                                        $newValue = $record->fresh()->contract_value;
                                        $set('contract_value', $newValue);
                                        \Filament\Notifications\Notification::make()
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
                    
                    Tables\Actions\Action::make('cashflow')
                        ->label('Monitoring Arus Kas')
                        ->icon('heroicon-o-presentation-chart-bar')
                        ->url(fn (Project $record) => CashFlowDashboard::getUrl(['record' => $record])),

                    Tables\Actions\Action::make('termins')
                        ->label('Manajemen Termin')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->url(fn (Project $record) => ManageProjectTermins::getUrl(['record' => $record])),
                    Tables\Actions\Action::make('resource_plan')
                        ->label('Rencana Belanja')
                        ->icon('heroicon-o-shopping-cart')
                        ->url(fn (Project $record) => ManageResourcePlan::getUrl(['record' => $record])),
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
            'cashflow' => CashFlowDashboard::route('/{record}/cash-flow'),
            'termins'  => ManageProjectTermins::route('/{record}/termins'),
            'resource_plan' => ManageResourcePlan::route('/{record}/resource'),
        ];
    }
}