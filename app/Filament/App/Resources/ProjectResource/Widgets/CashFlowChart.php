<?php

namespace App\Filament\App\Resources\ProjectResource\Widgets;

use App\Models\Project;
use App\Services\CashFlowService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class CashFlowChart extends ChartWidget
{
    protected static ?string $heading = 'Arus Kas: Rencana vs Realisasi';
    protected static ?string $maxHeight = '400px';
    
    // Properti record dari Resource Page
    public ?Model $record = null;

    protected function getData(): array
    {
        if (!$this->record || !($this->record instanceof Project)) {
            return [];
        }

        $service = new CashFlowService();
        // Ambil data kronologis (hasil olahan service yang kita tes di terminal)
        $rawData = $service->generateCashFlowData($this->record);

        // --- DATA PROCESSING FOR CHART ---
        // Karena satu hari bisa ada banyak transaksi, kita Grouping by Date
        // dan ambil saldo TERAKHIR di hari tersebut (Closing Balance).
        
        $groupedData = $rawData->groupBy('date')->map(function ($transactions, $date) {
            // Ambil transaksi terakhir di hari itu untuk mendapatkan saldo akhir hari
            $lastTransaction = $transactions->last();
            return [
                'date' => $date, // Tanggal (Y-m-d)
                'balance_plan' => $lastTransaction['balance_plan'],
                'balance_actual' => $lastTransaction['balance_actual'],
            ];
        });

        // Siapkan Array untuk Chart.js
        $labels = $groupedData->keys()->toArray(); // Sumbu X (Tanggal)
        $dataPlan = $groupedData->pluck('balance_plan')->toArray(); // Sumbu Y1
        $dataActual = $groupedData->pluck('balance_actual')->toArray(); // Sumbu Y2

        return [
            'datasets' => [
                [
                    'label' => 'Saldo Rencana (Plan)',
                    'data' => $dataPlan,
                    'borderColor' => '#3b82f6', // Biru
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3, // Garis melengkung halus
                ],
                [
                    'label' => 'Saldo Realisasi (Actual)',
                    'data' => $dataActual,
                    'borderColor' => '#22c55e', // Hijau
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}