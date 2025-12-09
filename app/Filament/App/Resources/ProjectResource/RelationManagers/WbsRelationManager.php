<?php

namespace App\Filament\App\Resources\ProjectResource\RelationManagers;

use App\Filament\App\Resources\WbsResource; // Kita akan buat ini sebentar lagi
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WbsRelationManager extends RelationManager
{
    protected static string $relationship = 'wbs';
    protected static ?string $title = 'Struktur Pekerjaan (WBS)';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Pekerjaan (Misal: Pekerjaan Persiapan)'),
                Forms\Components\Select::make('parent_id')
                    ->label('Induk Pekerjaan')
                    ->options(function ($livewire) {
                        // Hanya tampilkan WBS dari project yang sama
                        return $livewire->getOwnerRecord()->wbs()->pluck('name', 'id');
                    })
                    ->searchable(),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Induk')->color('gray'),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Jml Item'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Custom Action untuk masuk ke Detail RAB
                Tables\Actions\Action::make('manage_rab')
                    ->label('Isi RAB')
                    ->icon('heroicon-o-calculator')
                    ->url(fn ($record) => WbsResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}