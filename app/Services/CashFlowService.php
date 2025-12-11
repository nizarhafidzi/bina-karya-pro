<?php

namespace App\Services;

use App\Models\Project;
use App\Models\CashFlowPlan;
use App\Models\CashFlowActual;
use App\Models\WeeklyRealization;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CashFlowService
{
    /**
     * Mengambil ringkasan data proyek untuk header dashboard.
     *
     * @param Project $project
     * @return array
     */
    public function getProjectSummary(Project $project): array
    {
        // Hitung total Pemasukan Realisasi (Uang Masuk)
        $totalIn = CashFlowActual::where('project_id', $project->id)
            ->where('type', 'in')
            ->sum('amount');

        // Hitung total Pengeluaran Realisasi (Uang Keluar)
        $totalOut = CashFlowActual::where('project_id', $project->id)
            ->where('type', 'out')
            ->sum('amount');

        // Hitung Sisa Kas (Cash on Hand)
        $currentBalance = $totalIn - $totalOut;

        return [
            'total_income' => $totalIn,
            'total_expense' => $totalOut,
            'current_balance' => $currentBalance,
            'burn_rate' => 0, // Bisa dikembangkan nanti: rata-rata pengeluaran per minggu
        ];
    }

    /**
     * Menghasilkan Data Arus Kas (Plan vs Actual) secara kronologis.
     * Digunakan untuk Tabel Analisa dan Grafik Kurva.
     * * @param Project $project
     * @return Collection
     */
    public function generateCashFlowData(Project $project): Collection
    {
        // 1. Ambil Data Rencana (Plan)
        // Kita format agar strukturnya seragam dengan Actual
        $plans = CashFlowPlan::where('project_id', $project->id)
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'type' => 'plan', // Penanda
                    'category' => $item->type, // in (masuk) / out (keluar)
                    // Jika Income positif, Expense negatif
                    'amount' => $item->type === 'in' ? $item->amount : -$item->amount, 
                    'description' => $item->description,
                ];
            });

        // 2. Ambil Data Realisasi (Actual)
        $actuals = CashFlowActual::where('project_id', $project->id)
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'type' => 'actual',
                    'category' => $item->type,
                    'amount' => $item->type === 'in' ? $item->amount : -$item->amount,
                    'description' => $item->description,
                ];
            });

        // 3. Gabungkan (Merge) dan Urutkan berdasarkan Tanggal
        $timeline = $plans->merge($actuals)->sortBy('date');

        // 4. Hitung Saldo Berjalan (Running Balance)
        // Kita butuh variabel di luar loop untuk menyimpan state saldo terakhir
        $runningBalancePlan = 0;
        $runningBalanceActual = 0;

        return $timeline->map(function ($item) use (&$runningBalancePlan, &$runningBalanceActual) {
            
            // Logika Saldo Kumulatif:
            // Saldo Saat Ini = Saldo Sebelumnya + Transaksi Saat Ini
            
            if ($item['type'] === 'plan') {
                $runningBalancePlan += $item['amount'];
            } else {
                $runningBalanceActual += $item['amount'];
            }

            return [
                'date' => $item['date'],
                'description' => $item['description'],
                'flow_type' => $item['category'] === 'in' ? 'Pemasukan' : 'Pengeluaran',
                'amount' => abs($item['amount']), // Tampilkan angka positif di tabel
                'source' => $item['type'] === 'plan' ? 'Rencana' : 'Realisasi',
                
                // Ini data paling penting untuk Grafik:
                'balance_plan' => $runningBalancePlan,
                'balance_actual' => $runningBalanceActual,
            ];
        });
    }

    public function getMonthlyChartData(Project $project): array
    {
        // 1. Ambil Data Actual
        $transactions = CashFlowActual::where('project_id', $project->id)
            ->orderBy('date')
            ->get();

        // 2. Grouping per Bulan (Y-m)
        $grouped = $transactions->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m');
        });

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        foreach ($grouped as $month => $items) {
            $labels[] = Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');
            $incomeData[] = $items->where('type', 'in')->sum('amount');
            $expenseData[] = $items->where('type', 'out')->sum('amount');
        }

        // Return struktur data Chart.js
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pemasukan (Income)',
                    'data' => $incomeData,
                    'backgroundColor' => '#22c55e', // Green
                    'borderColor' => '#16a34a',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Pengeluaran (Expense)',
                    'data' => $expenseData,
                    'backgroundColor' => '#ef4444', // Red
                    'borderColor' => '#dc2626',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * Menyiapkan Ringkasan Keuangan Khusus Owner.
     * Fokus pada: Contract Value, Total Paid, dan Kesehatan Pembayaran.
     */
    public function getOwnerFinancialSummary(Project $project): array
    {
        // 1. Total Uang yang sudah dibayar Owner (Actual Income)
        $totalPaidByOwner = CashFlowActual::where('project_id', $project->id)
            ->where('type', 'in')
            ->sum('amount');

        // 2. Progress Fisik Terakhir (%)
        $lastProgress = WeeklyRealization::where('project_id', $project->id)
            ->orderByDesc('week')
            ->value('realized_progress') ?? 0;

        // 3. Nilai Wajar (Seharusnya dibayar berdasarkan progress fisik)
        // Rumus: Contract Value * % Progress
        $fairValue = $project->contract_value * ($lastProgress / 100);

        // 4. Analisa Kesehatan (Safety Check)
        // Jika Owner bayar jauh lebih banyak dari progress fisik, itu RISK.
        $paymentGap = $totalPaidByOwner - $fairValue;
        
        // Threshold: Jika selisih lebih dari 10% nilai kontrak
        $tolerance = $project->contract_value * 0.10; 

        $status = 'healthy'; // Default Sehat
        $message = 'Pembayaran sesuai progress.';

        if ($paymentGap > $tolerance) {
            $status = 'overpaid';
            $message = 'Pembayaran mendahului progress fisik (Risk).';
        } elseif ($paymentGap < -$tolerance) {
            $status = 'underpaid';
            $message = 'Pembayaran terlambat dari progress fisik.';
        }

        return [
            'contract_value' => $project->contract_value,
            'total_paid' => $totalPaidByOwner,
            'remaining_contract' => $project->contract_value - $totalPaidByOwner,
            'physical_progress' => $lastProgress,
            'paid_percentage' => $project->contract_value > 0 ? ($totalPaidByOwner / $project->contract_value) * 100 : 0,
            'health_status' => $status,
            'health_message' => $message,
        ];
    }
}