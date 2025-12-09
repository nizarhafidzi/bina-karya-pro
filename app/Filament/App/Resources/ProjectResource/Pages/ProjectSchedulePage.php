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

            foreach ($this->scheduleInputs as $itemId => $data) {
                $start = (int)$data['start_week'];
                $end = (int)$data['end_week'];
                $percentages = $data['weekly_distribution'];

                // AUTO-NORMALIZE: Paksa jadi 100% jika selisih dikit
                $sum = array_sum($percentages);
                if (abs($sum - 100) > 0.001 && $sum > 0) {
                    $scale = 100 / $sum;
                    $percentages = array_map(fn($val) => $val * $scale, $percentages);
                }

                $rabItem = RabItem::find($itemId);
                if ($rabItem) {
                    $rabItem->update(['start_week' => $start, 'end_week' => $end]);
                    ProjectSchedule::where('rab_item_id', $itemId)->delete();

                    foreach ($percentages as $index => $percent) {
                        ProjectSchedule::create([
                            'team_id' => $this->record->team_id,
                            'rab_item_id' => $itemId,
                            'week' => $start + $index,
                            'progress_plan' => $percent,
                        ]);
                    }
                }
            }

            DB::commit();
            Notification::make()->title('Jadwal Tersimpan')->success()->send();
            $this->redirect(ProjectResource::getUrl('progress', ['record' => $this->record]));

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }
}