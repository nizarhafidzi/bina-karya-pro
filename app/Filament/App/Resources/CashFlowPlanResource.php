<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashFlowPlanResource\Pages;
use App\Models\CashFlowPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class CashFlowPlanResource extends Resource
{
    protected static ?string $model = CashFlowPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Rencana Anggaran (Plan)';
    protected static ?string $navigationGroup = 'Keuangan Proyek';
    protected static ?int $navigationSort = 1;

    // Filter Tenant Otomatis
    protected static ?string $tenantOwnershipRelationshipName = 'team';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Input Rencana')
                    ->schema([
                        // Filter Project: Hanya tampilkan project milik Team ini
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name', function (Builder $query) {
                                return $query->where('team_id', Filament::getTenant()->id);
                            })
                            ->required()
                            ->label('Proyek'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'in' => 'Pemasukan (Termin)',
                                'out' => 'Pengeluaran (Belanja)',
                            ])
                            ->required()
                            ->label('Jenis Transaksi'),

                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->label('Tanggal Rencana'),

                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Nominal'),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->label('Keterangan'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->searchable(),
                Tables\Columns\TextColumn::make('description')->limit(30),
                
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->alignment('right')
                    // Fitur Summarizer: Hitung total otomatis di bawah tabel
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('IDR')),
            ])
            ->defaultSort('date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashFlowPlans::route('/'),
            'create' => Pages\CreateCashFlowPlan::route('/create'),
            'edit' => Pages\EditCashFlowPlan::route('/{record}/edit'),
        ];
    }
}