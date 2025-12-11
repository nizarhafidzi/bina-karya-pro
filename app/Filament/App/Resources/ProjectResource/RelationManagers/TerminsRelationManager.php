<?php

namespace App\Filament\App\Resources\ProjectResource\RelationManagers;

use App\Services\TerminService;
use App\Models\CashFlowActual;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class TerminsRelationManager extends RelationManager
{
    protected static string $relationship = 'termins';
    protected static ?string $title = 'Jadwal Termin & Penagihan';
    protected static ?string $icon = 'heroicon-o-document-currency-dollar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Termin')
                    ->placeholder('Contoh: Termin 1 (20%)'),

                Forms\Components\TextInput::make('target_progress')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->label('Syarat Progress Fisik')
                    ->helperText('Termin ini bisa ditagih jika progress lapangan mencapai angka ini.'),

                Forms\Components\TextInput::make('percentage_value')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->label('Nilai Tagihan (%)')
                    ->live(onBlur: true) // Real-time calculation
                    ->afterStateUpdated(function (Forms\Set $set, $state, RelationManager $livewire) {
                        // Auto hitung Rupiah saat Persen diisi
                        if ($state && $livewire->getOwnerRecord()) {
                            $project = $livewire->getOwnerRecord();
                            $nominal = $project->contract_value * ($state / 100);
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Manajemen Termin')
            ->description('Atur jadwal pembayaran berdasarkan progress fisik.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('target_progress')
                    ->label('Syarat Progress')
                    ->suffix('%')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('percentage_value')
                    ->label('Nilai Tagihan')
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('nominal_value')
                    ->money('IDR')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'gray',
                        'ready' => 'warning', // KUNING: "Tagih Saya!"
                        'submitted' => 'info', // BIRU: "Sedang diproses"
                        'paid' => 'success', // HIJAU: "Cair"
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'ready' => 'heroicon-m-bell-alert', // Ikon Lonceng biar noticeable
                        'paid' => 'heroicon-m-check-badge',
                        default => '',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Terjadwal',
                        'ready' => 'SIAP DITAGIH',
                        'submitted' => 'Menunggu Pembayaran',
                        'paid' => 'Lunas',
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Termin'),
                
                // Action untuk Trigger Sinkronisasi Manual
                Tables\Actions\Action::make('sync_status')
                    ->label('Cek Kelayakan Tagih')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (RelationManager $livewire) {
                        $service = new TerminService();
                        $service->syncTerminStatus($livewire->getOwnerRecord());
                        Notification::make()->title('Status Termin Diperbarui')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // ACTION 1: Ajukan Tagihan (Planned/Ready -> Submitted)
                Tables\Actions\Action::make('submit_invoice')
                    ->label('Ajukan Invoice')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Model $record) => in_array($record->status, ['planned', 'ready']))
                    ->action(fn (Model $record) => $record->update(['status' => 'submitted']))
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Invoice ke Owner?')
                    ->modalDescription('Pastikan dokumen pendukung sudah lengkap.'),

                // ACTION 2: Konfirmasi Bayar (Submitted -> Paid)
                Tables\Actions\Action::make('mark_paid')
                    ->label('Terima Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->status === 'submitted')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Terima')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan (No. Referensi Bank)'),
                    ])
                    ->action(function (Model $record, array $data) {
                        // 1. Update status termin
                        $record->update(['status' => 'paid']);

                        // 2. OTOMATIS Catat ke Cash Flow Actual (Integrasi Phase 6)
                        // Agar admin tidak perlu input ulang di menu Cash Flow
                        CashFlowActual::create([
                            'team_id' => $record->project->team_id, // Penting untuk tenancy
                            'project_id' => $record->project_id,
                            'type' => 'in', // Pemasukan
                            'category' => 'termin',
                            'amount' => $record->nominal_value,
                            'date' => $data['payment_date'],
                            'description' => "Pembayaran {$record->name} (Auto from Termin)",
                        ]);

                        Notification::make()
                            ->title('Pembayaran Diterima')
                            ->body('Data otomatis tercatat di Arus Kas.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}