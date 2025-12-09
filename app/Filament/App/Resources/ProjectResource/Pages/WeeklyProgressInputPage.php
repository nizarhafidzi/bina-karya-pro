<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\ItemRealization;
use App\Models\WeeklyRealization;
use App\Services\CurveCalculatorService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\DB;

class WeeklyProgressInputPage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;
    protected static string $view = 'filament.app.resources.project-resource.pages.weekly-progress-input-page';
    protected static ?string $title = 'Input Progress Mingguan (Opname)';

    // Data Binding untuk Form
    public $selectedWeek;
    public $progressData = []; // Array untuk menampung inputan user
    public $weekOptions = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // 1. Generate Opsi Minggu (1 s/d 52 atau Durasi Proyek)
        // Kita ambil max week dari jadwal yang sudah dibuat
        $maxScheduleWeek = $this->record->projectSchedules()->max('week') ?? 52;
        for ($i = 1; $i <= $maxScheduleWeek; $i++) {
            $this->weekOptions[$i] = 'Minggu ke-' . $i;
        }

        // Default ke minggu pertama atau minggu terakhir yang belum diisi
        $lastRealization = $this->record->weeklyRealizations()->max('week') ?? 0;
        $this->selectedWeek = $lastRealization + 1;
        
        // Load data item
        $this->loadItems();
    }

    // Dipanggil saat User ganti Dropdown Minggu
    public function updatedSelectedWeek()
    {
        $this->loadItems();
    }

    public function loadItems()
    {
        if (!$this->selectedWeek) return;

        // 1. Pastikan Bobot Item Terbaru
        $service = new CurveCalculatorService();
        $service->calculateItemWeights($this->record);

        $items = $this->record->rabItems;
        $this->progressData = [];

        foreach ($items as $item) {
            // A. Cari Progress Minggu LALU (Kumulatif Sebelumnya)
            // Ambil realisasi di minggu sebelum minggu yang dipilih
            $prevItemReal = ItemRealization::whereHas('weeklyRealization', function($q) {
                    $q->where('project_id', $this->record->id)
                      ->where('week', '<', $this->selectedWeek);
                })
                ->where('rab_item_id', $item->id)
                ->orderByDesc('id') // Ambil yang paling baru
                ->first();

            $prevProgress = $prevItemReal ? $prevItemReal->progress_cumulative : 0;

            // B. Cari Progress Minggu INI (Jika user mau edit data lama)
            $currItemReal = ItemRealization::whereHas('weeklyRealization', function($q) {
                    $q->where('project_id', $this->record->id)
                      ->where('week', $this->selectedWeek);
                })
                ->where('rab_item_id', $item->id)
                ->first();

            $currentProgress = $currItemReal ? $currItemReal->progress_cumulative : $prevProgress;

            // Masukkan ke array data untuk UI
            $this->progressData[$item->id] = [
                'name' => $item->ahsMaster->name ?? 'Item #' . $item->id,
                'weight' => $item->weight, // Bobot Item (%)
                'prev_cumulative' => $prevProgress, // % Selesai s.d. minggu lalu
                'current_cumulative' => $currentProgress, // Inputan User
            ];
        }
    }

    public function saveProgress()
    {
        DB::beginTransaction();
        try {
            // 1. Buat/Update Header Weekly Realization
            $weeklyRealization = WeeklyRealization::updateOrCreate(
                [
                    'project_id' => $this->record->id,
                    'week' => $this->selectedWeek,
                ],
                [
                    'team_id' => $this->record->team_id,
                    'start_date' => now(), // Opsional: Bisa diambil dari input tanggal
                    'end_date' => now(),
                    'status' => 'submitted',
                ]
            );

            $totalProjectProgressThisWeek = 0; // % Kontribusi mingguan ke proyek

            // 2. Loop semua item untuk simpan detail
            foreach ($this->progressData as $itemId => $data) {
                $prevCum = (float) $data['prev_cumulative'];
                $currCum = (float) $data['current_cumulative'];
                $itemWeight = (float) $data['weight'];

                // Validasi: Tidak boleh kurang dari minggu lalu, tidak boleh lebih dari 100
                if ($currCum < $prevCum) $currCum = $prevCum;
                if ($currCum > 100) $currCum = 100;

                // Hitung Kenaikan Minggu Ini (Delta)
                // Contoh: Minggu lalu 50%, Sekarang 60%. Delta = 10%
                $deltaProgress = $currCum - $prevCum;

                // Hitung Kontribusi ke Global Project
                // Rumus: Delta * (Bobot / 100)
                // Contoh: Naik 10% * Bobot 5% = Nambah 0.5% ke kurva S
                $contribution = $deltaProgress * ($itemWeight / 100);
                $totalProjectProgressThisWeek += $contribution;

                // Simpan Detail Item
                ItemRealization::updateOrCreate(
                    [
                        'weekly_realization_id' => $weeklyRealization->id,
                        'rab_item_id' => $itemId,
                    ],
                    [
                        'progress_this_week' => $deltaProgress,
                        'progress_cumulative' => $currCum,
                    ]
                );
            }

            // 3. Simpan Total Realisasi Mingguan ke Header (Agar Kurva S enteng bacanya)
            // Hati-hati: Kurva S membaca "Realisasi MINGGUAN" atau "KUMULATIF"?
            // Berdasarkan CurveCalculatorService tadi: $cumulativeActual += $real->realized_progress;
            // Berarti kita simpan parsial mingguan di sini.
            
            $weeklyRealization->update([
                'realized_progress' => $totalProjectProgressThisWeek
            ]);

            DB::commit();

            Notification::make()
                ->title('Progress Tersimpan')
                ->body("Realisasi Minggu ke-{$this->selectedWeek} berhasil diupdate. (Progress: +".number_format($totalProjectProgressThisWeek, 2)."%)")
                ->success()
                ->send();

            // Redirect balik ke Monitoring
            $this->redirect(ProjectResource::getUrl('progress', ['record' => $this->record]));

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }
}