<?php

namespace App\Filament\App\Resources\ProjectResource\RelationManagers;

use App\Models\AhsMaster;
use App\Models\RabItem; // Pastikan import Model RabItem
use App\Models\RabItemMaterial;
use App\Models\ResourcePrice;
use App\Models\Wbs;
use App\Services\RabCalculatorService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder; // Import Builder
use Illuminate\Database\Eloquent\Model;

class RabItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'rabItems';

    protected static ?string $title = 'Rencana Anggaran Biaya (RAB)';
    
    protected static ?string $icon = 'heroicon-o-calculator';

    public function form(Form $form): Form
    {
        // ... (Kode Form TETAP SAMA seperti sebelumnya, tidak perlu diubah)
        return $form
            ->schema([
                Forms\Components\Select::make('wbs_id')
                    ->label('Kategori Pekerjaan (WBS)')
                    ->options(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()->wbs()->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Nama Pekerjaan Baru (Misal: Pekerjaan Atap)'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(1)
                    ])
                    ->createOptionUsing(function ($data, RelationManager $livewire) {
                        return $livewire->getOwnerRecord()->wbs()->create($data)->id;
                    })
                    ->columnSpanFull(),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Select::make('ahs_master_id')
                        ->label('Analisa Harga (AHS)')
                        ->options(AhsMaster::all()->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->helperText('Pilih resep pekerjaan, harga akan otomatis dihitung.'),
                    
                    Forms\Components\TextInput::make('qty')
                        ->label('Volume')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    
                    Forms\Components\TextInput::make('unit')
                        ->label('Satuan')
                        ->placeholder('m3/m2/ls')
                        ->required(),
                ])->columns(3)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // --- UI GROUPING ---
            ->defaultGroup('wbs.name') 
            ->groups([
                Tables\Grouping\Group::make('wbs.name')
                    ->label('Pekerjaan')
                    ->collapsible(),
            ])
            // -------------------
            
            ->columns([
                Tables\Columns\TextColumn::make('ahsMaster.name')
                    ->label('Uraian Pekerjaan')
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('qty')
                    ->label('Vol')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('unit')
                    ->label('Sat')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Sat.')
                    ->money('IDR')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Subtotal')
                            ->money('IDR'),
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Item (+)')
                    ->modalHeading('Tambah Item RAB')
                    ->slideOver()
                    
                    // --- PENTING: BYPASS HAS_MANY_THROUGH ---
                    // Karena HasManyThrough tidak mendukung create() langsung, 
                    // dan kita sudah punya wbs_id di form, kita pakai create manual.
                    ->using(function (array $data, string $model): Model {
                        return $model::create($data);
                    })
                    // ----------------------------------------

                    ->after(function ($record) {
                        // Logic Snapshot (TETAP SAMA)
                        $wbs = $record->wbs;
                        $project = $wbs->project;
                        $ahs = $record->ahsMaster;
                        $tenantId = Filament::getTenant()->id;

                        foreach ($ahs->coefficients as $coef) {
                            $resourcePrice = ResourcePrice::where('resource_id', $coef->resource_id)
                                ->where('region_id', $project->region_id)
                                ->where(function($q) use ($tenantId) {
                                    $q->where('team_id', $tenantId)->orWhereNull('team_id');
                                })
                                ->orderBy('team_id', 'desc')->orderBy('year', 'desc')->first();

                            $finalPrice = $resourcePrice ? $resourcePrice->price : $coef->resource->default_price;

                            RabItemMaterial::create([
                                'rab_item_id' => $record->id,
                                'resource_name' => $coef->resource->name,
                                'unit' => $coef->resource->unit,
                                'coefficient' => $coef->coefficient,
                                'price' => $finalPrice,
                                'subtotal' => $coef->coefficient * $finalPrice,
                            ]);
                        }
                        (new RabCalculatorService())->calculateItem($record);

                        // 2. KIRIM SINYAL UPDATE (BARU)
                        $this->dispatch('refresh-contract-value');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->after(function ($record) {
                        $record->update(['total_price' => $record->unit_price * $record->qty]);
                        (new RabCalculatorService())->calculateProject($record->wbs->project);
                        
                        // KIRIM SINYAL UPDATE (BARU)
                        $this->dispatch('refresh-contract-value');
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        $wbsId = $record->wbs_id;
                        $wbs = Wbs::find($wbsId);
                        if ($wbs && $wbs->project) {
                            (new RabCalculatorService())->calculateProject($wbs->project);
                        }

                        // KIRIM SINYAL UPDATE (BARU)
                        $this->dispatch('refresh-contract-value');
                    }),
            ]);
    }

    // --- TAMBAHKAN FUNCTION INI (SOLUSI ERROR SQL) ---
    protected function getTableQuery(): ?Builder
    {
        // Kita ambil ID Project yang sedang dibuka
        $projectId = $this->getOwnerRecord()->id;
        
        // Kita override query-nya:
        // "Ambil RabItem yang punya WBS, dimana WBS-nya milik Project ini"
        // Cara ini menggunakan 'EXISTS' query, bukan 'JOIN', jadi aman dari konflik nama tabel.
        return RabItem::query()
            ->whereHas('wbs', function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            });
    }
}