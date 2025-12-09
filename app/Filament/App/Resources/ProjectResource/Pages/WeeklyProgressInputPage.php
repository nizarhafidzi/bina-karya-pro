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

    public $currentWeek = 1;
    public $progressInputs = [];
    public $startDate;
    public $endDate;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // Default ke minggu selanjutnya dari opname terakhir
        $lastOpname = WeeklyRealization::where('project_id', $this->record->id)->max('week');
        $this->currentWeek = $lastOpname ? $lastOpname + 1 : 1;
        
        $this->setDateRange();
        $this->loadProgressData();
    }

    public function updatedCurrentWeek()
    {
        $this->setDateRange();
        $this->loadProgressData();
    }

    public function setDateRange()
    {
        // Asumsi proyek mulai sesuai start_date. Minggu 1 = start_date + 6 hari.
        if ($this->record->start_date) {
            $start = $this->record->start_date->copy()->addWeeks($this->currentWeek - 1);
            $this->startDate = $start->format('Y-m-d');
            $this->endDate = $start->copy()->addDays(6)->format('Y-m-d');
        }
    }

    public function loadProgressData()
    {
        $this->progressInputs = [];

        // Cek apakah sudah ada laporan di minggu ini?
        $existing = WeeklyRealization::where('project_id', $this->record->id)
            ->where('week', $this->currentWeek)
            ->with('itemRealizations')
            ->first();

        $items = $this->record->rabItems;

        foreach ($items as $item) {
            // Cari data progress sebelumnya (Kumulatif minggu lalu)
            $prevRealization = ItemRealization::where('rab_item_id', $item->id)
                ->whereHas('weeklyRealization', function($q) {
                    $q->where('project_id', $this->record->id)
                      ->where('week', '<', $this->currentWeek);
                })
                ->orderByDesc('created_at')
                ->first();

            $prevCum = $prevRealization ? $prevRealization->progress_cumulative : 0;

            // Cari data inputan saat ini (jika edit mode)
            $currentVal = 0;
            if ($existing) {
                $itemRec = $existing->itemRealizations->where('rab_item_id', $item->id)->first();
                $currentVal = $itemRec ? $itemRec->progress_this_week : 0;
            }

            // Tampilkan hanya jika item ini belum selesai 100% atau sedang dikerjakan
            if ($prevCum < 100 || $currentVal > 0) {
                $this->progressInputs[$item->id] = [
                    'name' => $item->ahsMaster->name,
                    'weight' => $item->weight,
                    'prev_cumulative' => $prevCum,
                    'this_week' => $currentVal, // Input User
                    'max_input' => 100 - $prevCum, // Validasi agar tidak > 100%
                ];
            }
        }
    }

    public function submitOpname()
    {
        DB::beginTransaction();
        try {
            // 1. Create/Update Header
            $header = WeeklyRealization::updateOrCreate(
                [
                    'project_id' => $this->record->id,
                    'week' => $this->currentWeek,
                ],
                [
                    'team_id' => $this->record->team_id,
                    'start_date' => $this->startDate ?? now(),
                    'end_date' => $this->endDate ?? now(),
                    'status' => 'submitted',
                ]
            );

            // 2. Simpan Detail Item
            foreach ($this->progressInputs as $itemId => $data) {
                $thisWeek = (float)$data['this_week'];
                $prevCum = (float)$data['prev_cumulative'];
                
                // Validasi sederhana
                if ($thisWeek < 0) $thisWeek = 0;
                if (($prevCum + $thisWeek) > 100) $thisWeek = 100 - $prevCum;

                // Hanya simpan jika ada progress
                if ($thisWeek > 0 || $data['this_week'] !== 0) {
                    ItemRealization::updateOrCreate(
                        [
                            'weekly_realization_id' => $header->id,
                            'rab_item_id' => $itemId,
                        ],
                        [
                            'progress_this_week' => $thisWeek,
                            'progress_cumulative' => $prevCum + $thisWeek,
                        ]
                    );
                }
            }

            DB::commit();

            Notification::make()->title('Laporan Opname Berhasil Disimpan')->success()->send();
            
            // Redirect ke halaman Kurva S untuk lihat hasil
            $this->redirect(ProjectResource::getUrl('progress', ['record' => $this->record]));

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Gagal Menyimpan')->body($e->getMessage())->danger()->send();
        }
    }
}