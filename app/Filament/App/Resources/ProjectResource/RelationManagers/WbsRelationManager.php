<?php

namespace App\Filament\App\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WbsRelationManager extends RelationManager
{
    protected static string $relationship = 'wbs';

    protected static ?string $title = 'Struktur Pekerjaan (WBS)';
    
    protected static ?string $icon = 'heroicon-o-queue-list';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. Opsi Parent (Deep Hierarchy Logic)
                Forms\Components\Select::make('parent_id')
                    ->label('Induk Pekerjaan (Parent)')
                    ->options(function (RelationManager $livewire, ?Model $record) {
                        // Ambil Project ID dari Parent Record (Project)
                        $projectId = $livewire->getOwnerRecord()->id;

                        // Query WBS milik project ini saja
                        $query = \App\Models\Wbs::where('project_id', $projectId);

                        // Jika sedang EDIT, jangan tampilkan diri sendiri di opsi (cegah loop)
                        if ($record) {
                            $query->where('id', '!=', $record->id);
                        }

                        // Tampilkan dengan format hirarki sederhana (Nama)
                        // Idealnya pakai recursive function untuk indentasi "—", tapi ini cukup untuk MVP
                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih jika ini adalah sub-pekerjaan')
                    ->helperText('Kosongkan jika ini adalah Pekerjaan Utama (Level 1)'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Pekerjaan')
                    ->placeholder('Contoh: Pekerjaan Persiapan'),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(1)
                    ->label('Urutan')
                    ->helperText('Angka kecil muncul di atas'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            // Urutkan berdasarkan Parent ID (null duluan/Level 1), lalu Urutan
            ->defaultSort(fn ($query) => $query->orderByRaw('COALESCE(parent_id, 0), sort_order')) 
            ->columns([
                // Kolom Nama dengan Indikator Level
                Tables\Columns\TextColumn::make('name')
                    ->label('Uraian Pekerjaan')
                    ->searchable()
                    ->description(fn (Model $record) => $record->parent ? 'Sub dari: ' . $record->parent->name : 'Level Utama')
                    ->icon(fn (Model $record) => $record->parent ? 'heroicon-m-arrow-turn-down-right' : 'heroicon-m-folder')
                    ->iconColor(fn (Model $record) => $record->parent ? 'gray' : 'primary')
                    ->weight(fn (Model $record) => $record->parent ? 'normal' : 'bold'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->alignCenter()
                    ->sortable(),
                
                // Hitung jumlah item RAB di dalamnya
                Tables\Columns\TextColumn::make('rab_items_count')
                    ->counts('rabItems')
                    ->label('Jml Item')
                    ->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah WBS')
                    ->modalHeading('Buat Struktur Pekerjaan')
                    ->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->groupedBulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}