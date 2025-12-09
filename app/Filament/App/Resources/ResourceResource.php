<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ResourceResource\Pages;
use App\Models\Resource as ResourceModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Resources\ResourceResource\RelationManagers\ResourcePricesRelationManager;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Resource';

    protected static bool $isScopedToTenant = false;

    public static function getRelations(): array
    {
        return [
            // Daftarkan di sini
            ResourcePricesRelationManager::class,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\Select::make('category')
                    ->options([
                        'material' => 'Material/Bahan',
                        'labor' => 'Upah/Tenaga',
                        'equipment' => 'Alat',
                    ])->required(),
                Forms\Components\TextInput::make('unit')->required()->label('Satuan (m3/kg)'),
                Forms\Components\TextInput::make('default_price')->numeric()->prefix('Rp')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('default_price')->money('IDR'),
                
                // Indikator apakah ini data Global atau Custom
                Tables\Columns\TextColumn::make('team_id')
                    ->label('Source')
                    ->formatStateUsing(fn ($state) => $state ? 'Custom' : 'Global SNI')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'global' => 'Global SNI',
                        'custom' => 'Custom Company',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'global') {
                            $query->whereNull('team_id');
                        } elseif ($data['value'] === 'custom') {
                            $query->whereNotNull('team_id');
                        }
                    }),
            ])
            ->actions([
                // Kita sembunyikan tombol Edit/Delete jika data itu Global (team_id null)
                Tables\Actions\EditAction::make()
                    ->hidden(fn (ResourceModel $record) => $record->team_id === null),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (ResourceModel $record) => $record->team_id === null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResources::route('/'),
            'create' => Pages\CreateResource::route('/create'),
            'edit' => Pages\EditResource::route('/{record}/edit'),
        ];
    }
    
}