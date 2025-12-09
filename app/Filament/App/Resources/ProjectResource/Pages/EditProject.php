<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On; // Import Attribute On

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // --- TAMBAHKAN FUNGSI INI ---
    // Fungsi ini akan dipanggil otomatis saat ada sinyal 'refresh-contract-value'
    #[On('refresh-contract-value')]
    public function refreshContractValue(): void
    {
        // 1. Ambil data terbaru dari database (yang sudah dihitung kalkulator)
        $this->record->refresh();
        
        // 2. Isi ulang form dengan data baru (termasuk contract_value)
        $this->fillForm(); 
        
        // 3. (Opsional) Kirim notifikasi kecil
        // \Filament\Notifications\Notification::make()->title('Nilai Kontrak Terupdate')->success()->send();
    }
}
