<?php

namespace App\Filament\App\Resources\ResourceResource\RelationManagers;

use App\Models\Region;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResourcePricesRelationManager extends RelationManager
{
    // Pastikan relasi 'prices' ada di Model Resource
    protected static string $relationship = 'prices'; 

    protected static ?string $title = 'Harga Wilayah (Regional Prices)';
    protected static ?string $icon = 'heroicon-o-currency-dollar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('region_id')
                    ->label('Wilayah / Region')
                    ->options(Region::all()->pluck('name', 'id')) // Ambil Region Global
                    ->searchable()
                    ->preload()
                    ->required(),
                
                Forms\Components\TextInput::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->default(date('Y'))
                    ->required(),
                
                Forms\Components\TextInput::make('price')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                
                // RAHASIA: Otomatis mengisi team_id dengan Tenant yang sedang login
                Forms\Components\Hidden::make('team_id')
                    ->default(fn () => Filament::getTenant()->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // QUERY FILTER: Tampilkan Harga Tenant Sendiri + Harga Global
            ->modifyQueryUsing(fn (Builder $query) => $query->where(function($q) {
                $tenant = Filament::getTenant();
                if ($tenant) {
                    $q->where('team_id', $tenant->id)
                      ->orWhereNull('team_id');
                }
            }))
            ->columns([
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->weight('bold'),
                
                // Badge penanda sumber data
                Tables\Columns\TextColumn::make('team_id')
                    ->label('Sumber')
                    ->formatStateUsing(fn ($state) => $state ? 'Harga Saya' : 'Standar SNI')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Harga')
                    // Memastikan team_id terisi saat create
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['team_id'] = Filament::getTenant()->id;
                        return $data;
                    }),
            ])
            ->actions([
                // Edit/Delete HANYA JIKA data milik Tenant
                Tables\Actions\EditAction::make()
                    ->hidden(fn (Model $record) => $record->team_id === null),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (Model $record) => $record->team_id === null),
            ]);
    }
}