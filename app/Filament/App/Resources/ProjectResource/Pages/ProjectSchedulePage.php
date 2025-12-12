<?php

namespace App\Filament\App\Resources\ProjectResource\Pages;

use App\Filament\App\Resources\ProjectResource;
use App\Models\ProjectSchedule;
use App\Models\RabItem;
use App\Services\CurveCalculatorService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\DB;

class ProjectSchedulePage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;
    protected static string $view = 'filament.app.resources.project-resource.pages.project-schedule-page';
    protected static ?string $title = 'Perencanaan Jadwal (Time Schedule)';

    public array $scheduleInputs = [];
    public bool $showDetails = false;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->loadData();
    }

    public function loadData(): void
    {
        $items = $this->record->rabItems()->with('schedules')->get();

        foreach ($items as $item) {
            $start = $item->start_week ?? 1;
            $end = $item->end_week ?? 1;
            
            $weeklyData = [];
            
            if ($item->schedules->count() > 0) {
                foreach ($item->schedules->sortBy('week') as $sched) {
                    // Ambil Progress Fisik Item (0-100%)
                    $weeklyData[] = (float) $sched->progress_plan;
                }
            } else {
                $weeklyData = $this->calculateLinearDistribution($start, $end);
            }

            $this->scheduleInputs[$item->id] = [
                'name' => $item->ahsMaster->name ?? 'Item #' . $item->id,
                'weight' => $item->weight,
                'start_week' => $start,
                'end_week' => $end,
                'weekly_distribution' => $weeklyData,
                'total_check' => array_sum($weeklyData),
            ];
        }
    }

    private function calculateLinearDistribution($start, $end)
    {
        $duration = $end - $start + 1;
        if ($duration < 1) $duration = 1;
        
        $perWeek = 100 / $duration;
        $distribution = array_fill(0, $duration, round($perWeek, 2));
        
        // Fix rounding agar total pas 100
        $sum = array_sum($distribution);
        if ($sum != 100) {
            $distribution[count($distribution) - 1] += (100 - $sum);
        }
        
        return $distribution;
    }

    public function updatedScheduleInputs($value, $key)
    {
        // Logic auto-calculate saat user ganti tanggal
        $parts = explode('.', $key); 
        if (count($parts) < 3) return;
        
        $itemId = $parts[1];
        $field = $parts[2];

        if (in_array($field, ['start_week', 'end_week'])) {
            $data = $this->scheduleInputs[$itemId];
            $start = (int)($data['start_week'] ?? 1);
            $end = (int)($data['end_week'] ?? 1);

            if ($end < $start) { 
                $end = $start;
                $this->scheduleInputs[$itemId]['end_week'] = $start;
            }

            $newDist = $this->calculateLinearDistribution($start, $end);
            $this->scheduleInputs[$itemId]['weekly_distribution'] = $newDist;
            $this->scheduleInputs[$itemId]['total_check'] = 100;
        }
        
        if ($field === 'weekly_distribution') {
             $this->scheduleInputs[$itemId]['total_check'] = array_sum($this->scheduleInputs[$itemId]['weekly_distribution']);
        }
    }

    /**
     * CORE LOGIC: Menyimpan Detail Per Item
     * Inilah yang memperbaiki error "Resource Plan Not Found".
     */
    public function saveSchedule(): void
    {
        DB::beginTransaction();
        try {
            $service = new CurveCalculatorService();
            $service->calculateItemWeights($this->record);

            $insertData = [];
            $totalProjectProgress = 0;

            foreach ($this->scheduleInputs as $itemId => $data) {
                $start = (int)$data['start_week'];
                $end = (int)$data['end_week'];
                // Ambil array progress mingguan dari input
                $percentages = $data['weekly_distribution'] ?? [];

                // 1. NORMALISASI: Jika total user ngawur (misal 120%), kecilkan jadi skala 100%
                $rawTotal = array_sum($percentages);
                if ($rawTotal > 0 && abs($rawTotal - 100) > 0.1) {
                    $percentages = array_map(fn($v) => ($v / $rawTotal) * 100, $percentages);
                }

                // --- LOGIC PAKSA 100% (THE FIX) ---
                
                // Langkah A: Bulatkan semua ke 2 desimal dulu
                $percentages = array_map(fn($v) => round($v, 2), $percentages);

                // Langkah B: Hitung total setelah pembulatan
                $currentSum = array_sum($percentages);

                // Langkah C: Hitung selisih (Bisa positif 0.04 atau negatif -0.01)
                $diff = 100.00 - $currentSum;

                // Langkah D: Tempelkan selisih ke minggu TERAKHIR yang ada nilainya
                // Agar totalnya pas jadi 100.00
                if (abs($diff) > 0.00001 && count($percentages) > 0) {
                    // Cari index terakhir
                    $lastIndex = array_key_last($percentages);
                    
                    // Tambahkan diff (bisa menambah atau mengurangi)
                    $percentages[$lastIndex] += $diff;
                    
                    // Safety: Pastikan tidak jadi minus karena koreksi (jarang terjadi tapi mungkin)
                    if ($percentages[$lastIndex] < 0) {
                        // Jika minus, ambil dari minggu sebelumnya (mundur)
                        $percentages[$lastIndex] = 0;
                        $prevIndex = $lastIndex - 1;
                        if (isset($percentages[$prevIndex])) {
                            $percentages[$prevIndex] += $diff; // Bebankan ke minggu sebelumnya
                        }
                    }
                }
                // ------------------------------------

                // Update RAB Item Master
                $rabItem = RabItem::find($itemId);
                if ($rabItem) {
                    $rabItem->update(['start_week' => $start, 'end_week' => $end]);
                    $itemWeight = (float) $rabItem->weight;
                } else {
                    continue; 
                }

                // Masukkan ke Array Insert
                foreach ($percentages as $index => $percentPlan) {
                    if ($percentPlan <= 0) continue;

                    $currentWeek = $start + $index;
                    
                    // Hitung kontribusi untuk notifikasi (validasi visual)
                    $contribution = $itemWeight * ($percentPlan / 100);
                    $totalProjectProgress += $contribution;

                    $insertData[] = [
                        'project_id'    => $this->record->id,
                        'team_id'       => $this->record->team_id,
                        'rab_item_id'   => $itemId, 
                        'week'          => $currentWeek,
                        // Simpan angka yang sudah dipaksa pas 100
                        'progress_plan' => $percentPlan, 
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
            }

            // Hapus & Simpan Ulang
            ProjectSchedule::where('project_id', $this->record->id)->delete();
            
            foreach (array_chunk($insertData, 500) as $chunk) {
                ProjectSchedule::insert($chunk);
            }

            DB::commit();
            
            // Cek Total Akhir untuk User
            $totalFormatted = number_format($totalProjectProgress, 2);
            
            Notification::make()
                ->title('Jadwal Berhasil Disimpan')
                ->body("Sistem otomatis mengoreksi selisih koma. Total Bobot: {$totalFormatted}%")
                ->success()
                ->send();

            $this->redirect(ProjectResource::getUrl('progress', ['record' => $this->record]));

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
        }
    }
}