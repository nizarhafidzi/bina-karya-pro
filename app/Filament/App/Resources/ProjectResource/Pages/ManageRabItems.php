<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\AhsMaster;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\ResourcePrice;
use App\Models\Wbs;
use App\Services\RabCalculatorService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Imports\RabMultiSheetImport;
use App\Exports\TemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;

class ManageRabItems extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithRecord;
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.app.resources.project-resource.pages.manage-rab-items';

    protected static ?string $title = 'Rencana Anggaran Biaya (RAB)';
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    // --- LOGIC FORM (Copy dari Relation Manager) ---
    // Bedanya: $livewire->getOwnerRecord() diganti $this->record
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('wbs_id')
                    ->label('Kategori Pekerjaan (WBS)')
                    ->options(fn () => $this->record->wbs()->pluck('name', 'id')) // <--- Ganti $livewire
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required()->label('Nama Pekerjaan Baru'),
                        Forms\Components\TextInput::make('sort_order')->numeric()->default(1)
                    ])
                    ->createOptionUsing(fn ($data) => $this->record->wbs()->create($data)->id) // <--- Ganti
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
            ->query(
                RabItem::query()->whereHas('wbs', fn ($q) => $q->where('project_id', $this->record->id))
            )
            ->defaultGroup('wbs.name')
            ->groups([
                Tables\Grouping\Group::make('wbs.name')->label('Pekerjaan')->collapsible(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('ahsMaster.name')->label('Uraian Pekerjaan')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('qty')->label('Vol')->alignCenter(),
                Tables\Columns\TextColumn::make('unit')->label('Sat')->alignCenter(),
                Tables\Columns\TextColumn::make('unit_price')->label('Harga Sat.')->money('IDR')->color('gray'),
                Tables\Columns\TextColumn::make('total_price')->label('Total Harga')->money('IDR')->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()->label('Subtotal')->money('IDR'),
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('template')
                    ->label('Template Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                   ->action(fn() => Excel::download(
                        new TemplateExport(
                            // Headers sesuai logika import di atas
                            ['wbs_name', 'ahs_code', 'ahs_name', 'qty', 'unit'],
                            
                            // Contoh Data
                            [
                                ['Pekerjaan Persiapan', '', '', '', ''], // Baris Judul WBS
                                ['Pekerjaan Persiapan', 'AHS-001', 'Pembersihan Lapangan', 1, 'ls'], // Item
                                ['Pekerjaan Tanah', '', '', '', ''], // Baris Judul WBS Baru
                                ['Pekerjaan Tanah', 'AHS-TAN-02', 'Galian Tanah Biasa', 50.5, 'm3'], // Item
                            ]
                        ), 
                        'template_rab_multisheet.xlsx'
                    )),

                // ACTION: IMPORT RAB
                Tables\Actions\Action::make('import')
                    ->label('Import RAB')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('primary')
                    ->form([
                        FileUpload::make('file')
                            ->label('File Excel (.xlsx)')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            // Pass ID Project ke Constructor Import
                            Excel::import(new RabMultiSheetImport($this->record->id), $data['file']);
        
                            \Filament\Notifications\Notification::make()
                                ->title('Import RAB Berhasil')
                                ->body('Struktur WBS dan Item RAB telah dibuat.')
                                ->success()
                                ->send();
                                
                            $this->redirect(request()->header('Referer')); 
                            
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Import')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Item (+)')
                    ->modalHeading('Tambah Item RAB')
                    ->slideOver()
                    ->form(fn (Form $form) => $this->form($form)) // Panggil schema form di atas
                    ->using(function (array $data, string $model): Model {
                        return $model::create($data);
                    })
                    ->after(function ($record) {
                        // Logic Snapshot (Sama seperti Relation Manager)
                        $project = $this->record;
                        $ahs = $record->ahsMaster;
                        $tenantId = Filament::getTenant()->id;

                        if ($ahs) {
                            foreach ($ahs->coefficients as $coef) {
                                $resourcePrice = ResourcePrice::where('resource_id', $coef->resource_id)
                                    ->where('region_id', $project->region_id)
                                    ->where(fn($q) => $q->where('team_id', $tenantId)->orWhereNull('team_id'))
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
                        }
                        
                        (new RabCalculatorService())->calculateItem($record);
                        Notification::make()->title('Item RAB Ditambahkan')->success()->send();
                    }),
                
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->form(fn (Form $form) => $this->form($form))
                    ->after(function ($record) {
                        $record->update(['total_price' => $record->unit_price * $record->qty]);
                        (new RabCalculatorService())->calculateProject($this->record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(fn () => (new RabCalculatorService())->calculateProject($this->record)),
            ]);
    }
}