<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\CashFlowActual;
use App\Models\ProjectTermin;
use App\Services\TerminService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class ManageProjectTermins extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithRecord;
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.app.resources.project-resource.pages.manage-project-termins';

    protected static ?string $title = 'Manajemen Termin & Penagihan';
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProjectTermin::query()->where('project_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Termin')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('target_progress')
                    ->label('Syarat Progress')
                    ->suffix('%')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentage_value')
                    ->label('Nilai (%)')
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('nominal_value')
                    ->label('Nominal (Rp)')
                    ->money('IDR')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'gray',
                        'ready' => 'warning', // KUNING BERKEDIP (Notifikasi Visual)
                        'submitted' => 'info',
                        'paid' => 'success',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'ready' => 'heroicon-m-bell-alert',
                        'paid' => 'heroicon-m-check-badge',
                        default => '',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Terjadwal',
                        'ready' => 'SIAP DITAGIH',
                        'submitted' => 'Menunggu Pembayaran',
                        'paid' => 'Lunas',
                        default => ucfirst($state),
                    }),
            ])
            ->headerActions([
                // BUTTON 1: Tambah Termin Baru
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Termin')
                    ->icon('heroicon-m-plus')
                    ->form(fn (Form $form) => $this->terminForm($form))
                    ->using(function (array $data, string $model): Model {
                        // Manual create karena ini Custom Page, bukan Resource standar
                        return $model::create([
                            ...$data,
                            'project_id' => $this->record->id,
                        ]);
                    }),

                // BUTTON 2: Cek Status (Trigger Service)
                Tables\Actions\Action::make('sync_status')
                    ->label('Cek Kelayakan Tagih')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $service = new TerminService();
                        $service->syncTerminStatus($this->record);
                        
                        Notification::make()
                            ->title('Status Termin Diperbarui')
                            ->body('Sistem telah mengecek progress fisik terbaru.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn (Form $form) => $this->terminForm($form)),
                
                Tables\Actions\DeleteAction::make(),

                // ACTION: Ajukan Invoice
                Tables\Actions\Action::make('submit_invoice')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->button()
                    ->visible(fn (ProjectTermin $record) => in_array($record->status, ['planned', 'ready']))
                    ->action(fn (ProjectTermin $record) => $record->update(['status' => 'submitted']))
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Invoice?')
                    ->modalDescription('Status akan berubah menjadi Menunggu Pembayaran.'),

                // ACTION: Terima Pembayaran
                Tables\Actions\Action::make('mark_paid')
                    ->label('Terima Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->button()
                    ->visible(fn (ProjectTermin $record) => $record->status === 'submitted')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Terima')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan'),
                    ])
                    ->action(function (ProjectTermin $record, array $data) {
                        $record->update(['status' => 'paid']);

                        // Integrasi ke Cash Flow
                        CashFlowActual::create([
                            'team_id' => $this->record->team_id,
                            'project_id' => $this->record->id,
                            'type' => 'in',
                            'category' => 'termin',
                            'amount' => $record->nominal_value,
                            'date' => $data['payment_date'],
                            'description' => "Pembayaran Termin: {$record->name}",
                        ]);

                        Notification::make()->title('Pembayaran Diterima & Dicatat')->success()->send();
                    }),
            ]);
    }

    // Schema Form dipisah biar rapi
    public function terminForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->label('Nama Termin'),

            Forms\Components\TextInput::make('target_progress')
                ->required()
                ->numeric()
                ->suffix('%')
                ->label('Syarat Progress'),

            Forms\Components\TextInput::make('percentage_value')
                ->required()
                ->numeric()
                ->suffix('%')
                ->label('Nilai (%)')
                ->live(onBlur: true)
                ->afterStateUpdated(function (Forms\Set $set, $state) {
                    if ($state) {
                        $nominal = $this->record->contract_value * ($state / 100);
                        $set('nominal_value', $nominal);
                    }
                }),

            Forms\Components\TextInput::make('nominal_value')
                ->required()
                ->numeric()
                ->prefix('Rp')
                ->label('Nominal (Rp)'),
        ]);
    }
}