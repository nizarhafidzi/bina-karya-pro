<?php

namespace App\Filament\App\Resources\WbsResource\RelationManagers;

use App\Models\AhsMaster;
use App\Models\RabItemMaterial;
use App\Models\ResourcePrice;
use App\Services\RabCalculatorService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RabItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items'; // Relasi Wbs -> RabItems
    protected static ?string $title = 'Item Pekerjaan (RAB)';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ahs_master_id')
                    ->label('Pilih Analisa (AHS)')
                    ->options(AhsMaster::all()->pluck('name', 'id')) // Tampilkan Global & Custom
                    ->searchable()
                    ->required()
                    ->live() // Agar reaktif jika nanti mau preview harga
                    ->helperText('Memilih AHS akan mengunci harga bahan saat ini.'),
                
                Forms\Components\TextInput::make('qty')
                    ->label('Volume')
                    ->numeric()
                    ->default(1)
                    ->required(),
                
                Forms\Components\TextInput::make('unit')
                    ->label('Satuan')
                    ->required()
                    ->placeholder('m3/m2/ls'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ahsMaster.name')->label('Uraian Pekerjaan')->limit(50),
                Tables\Columns\TextColumn::make('qty')->label('Vol'),
                Tables\Columns\TextColumn::make('unit')->label('Sat'),
                Tables\Columns\TextColumn::make('unit_price')->money('IDR')->label('Harga Satuan'),
                Tables\Columns\TextColumn::make('total_price')->money('IDR')->label('Total Harga')->weight('bold'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Item Pekerjaan')
                    
                    // --- SNAPSHOT LOGIC ---
                    ->after(function ($record) {
                        // $record adalah RabItem yang baru dibuat
                        $wbs = $record->wbs;
                        $project = $wbs->project;
                        $ahs = $record->ahsMaster;
                        $tenantId = Filament::getTenant()->id;

                        // 1. Ambil Koefisien dari Master AHS
                        foreach ($ahs->coefficients as $coef) {
                            
                            // 2. Cari Harga di ResourcePrice berdasarkan Region Proyek
                            // Priority: Harga Custom Tenant > Harga Global > Harga Default Resource
                            $resourcePrice = ResourcePrice::where('resource_id', $coef->resource_id)
                                ->where('region_id', $project->region_id)
                                ->where(function($q) use ($tenantId) {
                                    $q->where('team_id', $tenantId)
                                      ->orWhereNull('team_id');
                                })
                                ->orderBy('team_id', 'desc') // Ambil Custom (ID) dulu baru Global (Null)
                                ->orderBy('year', 'desc') // Ambil tahun terbaru
                                ->first();

                            // Fallback ke harga dasar resource jika tidak ada harga region
                            $finalPrice = $resourcePrice ? $resourcePrice->price : $coef->resource->default_price;

                            // 3. CREATE SNAPSHOT (Inilah Kunci Integritas Data RAB)
                            RabItemMaterial::create([
                                'rab_item_id' => $record->id,
                                'resource_name' => $coef->resource->name, // Copy Nama (Aman dari rename master)
                                'unit' => $coef->resource->unit,
                                'coefficient' => $coef->coefficient,       // Copy Resep (Aman dari perubahan resep)
                                'price' => $finalPrice,                    // Copy Harga (Aman dari inflasi)
                                'subtotal' => $coef->coefficient * $finalPrice,
                            ]);
                        }

                        // 4. Hitung Total Item
                        $calculator = new RabCalculatorService();
                        $calculator->calculateItem($record);
                        // Optional: $calculator->calculateProject($project);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}