<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\WbsResource\RelationManagers\RabItemsRelationManager;
use App\Filament\App\Resources\WbsResource\Pages;
use App\Models\Wbs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WbsResource extends Resource
{
    protected static ?string $model = Wbs::class;
    
    protected static bool $shouldRegisterNavigation = false; 

    // --- TAMBAHKAN BARIS INI (SOLUSI) ---
    // Memberitahu Filament: "Jangan cari kolom team_id di tabel WBS, 
    // saya akan menjaganya lewat Project induknya."
    protected static bool $isScopedToTenant = false;
    // ------------------------------------

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->disabled(),
            ]);
    }

    // ... sisa method table, getRelations, dll tetap sama
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
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
            'index' => Pages\ListWbs::route('/'),
            'create' => Pages\CreateWbs::route('/create'),
            'edit' => Pages\EditWbs::route('/{record}/edit'),
        ];
    }
}