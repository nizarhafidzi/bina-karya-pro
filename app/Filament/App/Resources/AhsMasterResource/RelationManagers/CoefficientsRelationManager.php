<?php

namespace App\Filament\App\Resources\AhsMasterResource\RelationManagers;

use App\Models\Resource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class CoefficientsRelationManager extends RelationManager
{
    protected static string $relationship = 'coefficients';

    protected static ?string $title = 'Komponen Harga (Bahan/Upah/Alat)';

    protected static ?string $icon = 'heroicon-o-beaker';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('resource_id')
                    ->label('Pilih Resource')
                    ->options(function() {
                        // Tampilkan Resource Global DAN Resource Tenant
                        // Kita pakai raw query atau helper model Resource jika scope mengganggu
                        $tenantId = Filament::getTenant()->id;
                        return Resource::where('team_id', $tenantId)
                            ->orWhereNull('team_id')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive() // Agar bisa menampilkan satuan otomatis
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                        $set('unit_hint', Resource::find($state)?->unit ?? '-')
                    ),

                Forms\Components\TextInput::make('coefficient')
                    ->label('Koefisien')
                    ->numeric()
                    ->step(0.0001)
                    ->required()
                    ->suffix(fn ($get) => $get('unit_hint') ?? 'Unit'),
                
                // Hidden field bantu untuk display unit di form
                // --- PERBAIKAN DI SINI ---
                Forms\Components\Hidden::make('unit_hint')
                    ->dehydrated(false), // <--- TAMBAHKAN INI (JANGAN SIMPAN KE DB)
                // -------------------------
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Nama Bahan/Upah')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('resource.category')
                    ->badge()
                    ->colors([
                        'primary' => 'material',
                        'warning' => 'labor',
                        'success' => 'equipment',
                    ]),

                Tables\Columns\TextColumn::make('coefficient')
                    ->label('Koefisien (Indeks)')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('resource.unit')
                    ->label('Satuan'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Komponen'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}