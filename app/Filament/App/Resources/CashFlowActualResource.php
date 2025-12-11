<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashFlowActualResource\Pages;
use App\Models\CashFlowActual;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class CashFlowActualResource extends Resource
{
    protected static ?string $model = CashFlowActual::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Realisasi Kas (Actual)';
    protected static ?string $navigationGroup = 'Keuangan Proyek';
    protected static ?int $navigationSort = 2;

    protected static ?string $tenantOwnershipRelationshipName = 'team';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Catat Transaksi Nyata')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name', function (Builder $query) {
                                return $query->where('team_id', Filament::getTenant()->id);
                            })
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'in' => 'Uang Masuk',
                                'out' => 'Uang Keluar',
                            ])
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->label('Tanggal Transaksi'),

                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),

                        Forms\Components\Textarea::make('description')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->label('Tgl Transaksi'),
                Tables\Columns\TextColumn::make('project.name'),
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
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('IDR')),
            ])
            ->defaultSort('date', 'desc'); // Transaksi terbaru di atas
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashFlowActuals::route('/'),
            'create' => Pages\CreateCashFlowActual::route('/create'),
            'edit' => Pages\EditCashFlowActual::route('/{record}/edit'),
        ];
    }
}