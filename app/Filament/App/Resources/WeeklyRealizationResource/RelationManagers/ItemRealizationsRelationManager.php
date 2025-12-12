<?php

namespace App\Filament\App\Resources\WeeklyRealizationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemRealizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'itemRealizations';

    protected static ?string $title = 'Rincian Progress Item';

    public function form(Form $form): Form
    {
        // Read-only karena data ini hasil generate dari input opname
        return $form->schema([
            Forms\Components\TextInput::make('rab_item.ahsMaster.name')
                ->label('Item Pekerjaan'),
            Forms\Components\TextInput::make('progress_this_week')
                ->label('Progress Minggu Ini (%)'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('rabItem.ahsMaster.name')
                    ->label('Item Pekerjaan')
                    ->description(fn($record) => 'Vol: ' . $record->rabItem->qty . ' ' . $record->rabItem->unit)
                    ->wrap(),

                Tables\Columns\TextColumn::make('progress_this_week')
                    ->label('Realisasi Minggu Ini')
                    ->suffix('%')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('progress_cumulative')
                    ->label('Kumulatif')
                    ->suffix('%')
                    ->alignCenter()
                    ->color('gray'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([]) // Read only view
            ->bulkActions([]);
    }
}