<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AhsMasterResource\Pages;
use App\Filament\App\Resources\AhsMasterResource\RelationManagers\CoefficientsRelationManager;
use App\Models\AhsMaster;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class AhsMasterResource extends Resource
{
    protected static ?string $model = AhsMaster::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Analisa Harga (AHS)';
    protected static ?int $navigationSort = 2;

    // MATIKAN Auto-Scope agar bisa melihat data Global SNI
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Analisa')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Analisa (SNI)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Uraian Pekerjaan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // QUERY FILTER: Tampilkan AHS Milik Tenant + AHS Global (SNI)
            ->modifyQueryUsing(fn (Builder $query) => $query->where(function($q) {
                $tenant = Filament::getTenant();
                if ($tenant) {
                    $q->where('team_id', $tenant->id)
                      ->orWhereNull('team_id');
                }
            }))
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold')->limit(50),
                
                // Hitung total koefisien (optional, sekedar info)
                Tables\Columns\TextColumn::make('coefficients_count')
                    ->counts('coefficients')
                    ->label('Jml Item'),

                // Badge Source
                Tables\Columns\TextColumn::make('team_id')
                    ->label('Sumber')
                    ->formatStateUsing(fn ($state) => $state ? 'Custom' : 'SNI Global')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'global' => 'SNI Global',
                        'custom' => 'Custom Saya',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'global') $query->whereNull('team_id');
                        if ($data['value'] === 'custom') $query->whereNotNull('team_id');
                    }),
            ])
            ->actions([
                // Tombol Edit/Delete HANYA MUNCUL jika data milik Tenant
                Tables\Actions\EditAction::make()
                    ->hidden(fn (AhsMaster $record) => $record->team_id === null),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (AhsMaster $record) => $record->team_id === null),
            ])
            ->bulkActions([
                // Proteksi bulk delete juga
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Kita akan buat ini di Step 2
            CoefficientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAhsMasters::route('/'),
            'create' => Pages\CreateAhsMaster::route('/create'),
            'edit' => Pages\EditAhsMaster::route('/{record}/edit'),
        ];
    }
}