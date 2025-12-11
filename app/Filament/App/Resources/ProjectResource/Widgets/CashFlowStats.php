<?php

namespace App\Filament\App\Resources\ProjectResource\Widgets;

use App\Models\Project;
use App\Services\CashFlowService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CashFlowStats extends BaseWidget
{
    // Properti ini akan otomatis diisi oleh Filament saat widget dipasang di Resource Page
    public ?Model $record = null; 

    protected function getStats(): array
    {
        // Guard: Pastikan ada record project
        if (!$this->record || !($this->record instanceof Project)) {
            return [];
        }

        // Panggil Service Engine yang sudah kita tes tadi
        $service = new CashFlowService();
        $summary = $service->getProjectSummary($this->record);

        // Format angka ke Rupiah
        $format = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');

        return [
            Stat::make('Total Uang Masuk (Actual)', $format($summary['total_income']))
                ->description('Realisasi termin & modal')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Uang Keluar (Actual)', $format($summary['total_expense']))
                ->description('Realisasi belanja & upah')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Sisa Kas (Cash on Hand)', $format($summary['current_balance']))
                ->description('Dana tersedia saat ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($summary['current_balance'] >= 0 ? 'info' : 'danger') // Merah jika minus (Boncos)
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Dummy chart kecil (kosmetik)
        ];
    }
}