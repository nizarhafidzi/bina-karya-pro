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
    
    // Toggle untuk menampilkan detail per minggu (Default False agar UI Bersih)
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
                    $weeklyData[] = (float) $sched->progress_plan;
                }
            } else {
                // Jika data kosong, trigger logic auto-divide
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

    // Helper: Hitung Pembagian Rata
    private function calculateLinearDistribution($start, $end)
    {
        $duration = $end - $start + 1;
        if ($duration < 1) $duration = 1;
        
        $perWeek = 100 / $duration;
        $distribution = array_fill(0, $duration, round($perWeek, 2));
        
        // Fix selisih koma di elemen terakhir agar pas 100
        $sum = array_sum($distribution);
        if ($sum != 100) {
            $distribution[count($distribution) - 1] += (100 - $sum);
        }
        
        return $distribution;
    }

    // Listener: Saat User ganti Start/End -> Otomatis Bagi Rata
    public function updatedScheduleInputs($value, $key)
    {
        $parts = explode('.', $key); 
        if (count($parts) < 3) return;
        
        $itemId = $parts[1];
        $field = $parts[2];

        if (in_array($field, ['start_week', 'end_week'])) {
            $data = $this->scheduleInputs[$itemId];
            $start = (int)($data['start_week'] ?? 1);
            $end = (int)($data['end_week'] ?? 1);

            if ($end < $start) { // Auto fix rentang terbalik
                $end = $start;
                $this->scheduleInputs[$itemId]['end_week'] = $start;
            }

            // AUTO DIVIDE: Langsung update distribusi
            $newDist = $this->calculateLinearDistribution($start, $end);
            $this->scheduleInputs[$itemId]['weekly_distribution'] = $newDist;
            $this->scheduleInputs[$itemId]['total_check'] = 100;
        }
        
        // Update total check jika edit manual
        if ($field === 'weekly_distribution') {
             $this->scheduleInputs[$itemId]['total_check'] = array_sum($this->scheduleInputs[$itemId]['weekly_distribution']);
        }
    }

    public function saveSchedule(): void
    {
        DB::beginTransaction();
        try {
            $service = new CurveCalculatorService();
            $service->calculateItemWeights($this->record);

            $globalWeeklyProgress = [];
            $maxWeek = 0;

            foreach ($this->scheduleInputs as $itemId => $data) {
                // ... (Bagian pengambilan data ini TETAP SAMA) ...
                $start = (int)$data['start_week'];
                $end = (int)$data['end_week'];
                $itemWeight = (float) ($data['weight'] ?? 0); 
                $percentages = $data['weekly_distribution'] ?? [];
                
                // Safety check duration
                $duration = $end - $start + 1;
                if ($duration > 0 && count($percentages) !== $duration) {
                    $percentages = $this->calculateLinearDistribution($start, $end);
                }

                if ($end > $maxWeek) $maxWeek = $end;

                // Update RAB Item
                $rabItem = RabItem::find($itemId);
                if ($rabItem) {
                    $rabItem->update(['start_week' => $start, 'end_week' => $end]);
                }

                // Hitung Kontribusi (Masih presisi tinggi/float)
                foreach ($percentages as $index => $percentPlan) {
                    $currentWeek = $start + $index;
                    $contribution = $itemWeight * ($percentPlan / 100);

                    if (!isset($globalWeeklyProgress[$currentWeek])) {
                        $globalWeeklyProgress[$currentWeek] = 0;
                    }
                    $globalWeeklyProgress[$currentWeek] += $contribution;
                }
            }

            // --- PERBAIKAN LOGIC PEMBULATAN (SOLUSI 99.79%) ---
            
            // 1. Paksa setiap minggu jadi 2 desimal DULU (sesuai kemampuan Database)
            // Ini mencegah 'hilang koma' saat proses penyimpanan nanti
            for ($i = 1; $i <= $maxWeek; $i++) {
                if (isset($globalWeeklyProgress[$i])) {
                    $globalWeeklyProgress[$i] = round($globalWeeklyProgress[$i], 2);
                } else {
                    $globalWeeklyProgress[$i] = 0;
                }
            }

            // 2. Hitung Total setelah pembulatan
            $currentTotal = array_sum($globalWeeklyProgress);

            // 3. Teknik Sapu Jagat (Fixing Remainder)
            // Cek selisihnya dengan 100
            $diff = 100 - $currentTotal;

            // Jika selisihnya kecil (error pembulatan wajar), tempel ke minggu terakhir
            if ($maxWeek > 0 && abs($diff) < 1) {
                $globalWeeklyProgress[$maxWeek] += $diff;
            }
            // ---------------------------------------------------

            // Hapus Jadwal Lama
            ProjectSchedule::where('project_id', $this->record->id)->delete();

            // Simpan Jadwal Baru
            $insertData = [];
            for ($i = 1; $i <= $maxWeek; $i++) {
                $progress = $globalWeeklyProgress[$i];
                
                $insertData[] = [
                    'project_id'    => $this->record->id,
                    'team_id'       => $this->record->team_id,
                    'rab_item_id'   => null,
                    'week'          => $i,
                    // Sekarang aman untuk disimpan 2 desimal karena sudah kita atur di atas
                    'progress_plan' => $progress, 
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }

            if (count($insertData) > 0) {
                ProjectSchedule::insert($insertData);
            }

            DB::commit();
            
            // Notifikasi menampilkan total real yang akan dilihat user
            Notification::make()
                ->title('Jadwal Berhasil Disimpan')
                ->body("Kurva S terbentuk (Total: " . array_sum($globalWeeklyProgress) . "%)")
                ->success()
                ->send();

            $this->redirect(ProjectResource::getUrl('progress', ['record' => $this->record]));

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Gagal Menyimpan')->body($e->getMessage())->danger()->send();
        }
    }
}