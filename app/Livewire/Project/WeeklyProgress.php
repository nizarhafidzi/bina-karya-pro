<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\WeeklyRealization;
use App\Models\ItemRealization;
use App\Services\CurveCalculatorService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class WeeklyProgress extends Component
{
    public Project $project;
    
    // State
    public $selectedWeek;
    public $weekOptions = [];
    public $progressData = []; // Array penampung inputan user

    public function mount(Project $project)
    {
        $this->project = $project;
        
        // 1. Security Check (Hanya Manager & Admin)
        $myRoles = auth()->user()->roles->pluck('name')->toArray();
        if (!count(array_intersect($myRoles, ['Site Manager', 'Tenant Admin', 'Super Admin']))) {
            abort(403, 'Akses Ditolak: Halaman ini khusus Site Manager.');
        }

        // 2. Generate Opsi Minggu
        // Ambil max week dari jadwal (Plan) atau default 52 minggu
        $maxScheduleWeek = $this->project->projectSchedules()->max('week') ?? 52;
        for ($i = 1; $i <= $maxScheduleWeek; $i++) {
            $this->weekOptions[$i] = 'Minggu ke-' . $i;
        }

        // 3. Auto Select Week
        // Pilih minggu terakhir yang sudah diisi + 1, atau minggu 1
        $lastRealization = $this->project->weeklyRealizations()->max('week') ?? 0;
        $this->selectedWeek = $lastRealization + 1;

        // Jika sudah selesai semua, tetap di minggu terakhir
        if ($this->selectedWeek > $maxScheduleWeek) {
            $this->selectedWeek = $maxScheduleWeek;
        }

        $this->loadItems();
    }

    // Dipanggil saat dropdown minggu berubah
    public function updatedSelectedWeek()
    {
        $this->loadItems();
    }

    public function loadItems()
    {
        if (!$this->selectedWeek) return;

        // A. Update Bobot Terbaru (Penting agar sinkron dengan Kurva S)
        $service = new CurveCalculatorService();
        $service->calculateItemWeights($this->project);

        $items = $this->project->rabItems;
        $this->progressData = [];

        foreach ($items as $item) {
            // B. Cari Progress Minggu LALU (Baseline)
            // Query: Ambil realisasi di minggu < selectedWeek yg paling baru
            $prevItemReal = ItemRealization::whereHas('weeklyRealization', function($q) {
                    $q->where('project_id', $this->project->id)
                      ->where('week', '<', $this->selectedWeek);
                })
                ->where('rab_item_id', $item->id)
                ->orderByDesc('id') 
                ->first();

            $prevProgress = $prevItemReal ? (float)$prevItemReal->progress_cumulative : 0;

            // C. Cari Progress Minggu INI (Jika sedang edit data lama)
            $currItemReal = ItemRealization::whereHas('weeklyRealization', function($q) {
                    $q->where('project_id', $this->project->id)
                      ->where('week', $this->selectedWeek);
                })
                ->where('rab_item_id', $item->id)
                ->first();

            // Default input adalah progress minggu lalu (karena progress gak mungkin turun)
            $currentInputValue = $currItemReal ? (float)$currItemReal->progress_cumulative : $prevProgress;

            // Masukkan ke array UI
            $this->progressData[$item->id] = [
                'name' => $item->ahsMaster->name ?? 'Item #' . $item->id,
                'weight' => (float)$item->weight,
                'prev_cumulative' => $prevProgress,
                'current_cumulative' => $currentInputValue,
            ];
        }
    }

    public function saveProgress()
    {
        DB::beginTransaction();
        try {
            // 1. Buat Header Laporan Mingguan
            $weeklyRealization = WeeklyRealization::updateOrCreate(
                [
                    'project_id' => $this->project->id,
                    'week' => $this->selectedWeek,
                ],
                [
                    'team_id' => $this->project->team_id,
                    'start_date' => now(), // Opsional: logika tanggal start/end week
                    'end_date' => now(),
                    'status' => 'submitted',
                ]
            );

            $totalProjectProgressThisWeek = 0; // Akumulasi kontribusi ke Kurva S

            // 2. Loop semua item
            foreach ($this->progressData as $itemId => $data) {
                $prevCum = (float) $data['prev_cumulative'];
                $currCum = (float) $data['current_cumulative'];
                $itemWeight = (float) $data['weight'];

                // VALIDASI BACKEND: Tidak boleh mundur
                if ($currCum < $prevCum) $currCum = $prevCum;
                if ($currCum > 100) $currCum = 100;

                // Hitung Delta (Kenaikan minggu ini)
                $deltaProgress = $currCum - $prevCum;

                // Hitung Kontribusi ke Proyek (Rumus Kurva S)
                // Contoh: Naik 10% * Bobot Item 5% = Nambah 0.5% ke Proyek
                $contribution = $deltaProgress * ($itemWeight / 100);
                $totalProjectProgressThisWeek += $contribution;

                // Simpan Detail
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

            // 3. Update Total di Header (Untuk kemudahan query Kurva S)
            $weeklyRealization->update([
                'realized_progress' => $totalProjectProgressThisWeek
            ]);

            DB::commit();

            session()->flash('message', "Opname Minggu ke-{$this->selectedWeek} berhasil disimpan.");
            return redirect()->route('project.dashboard', $this->project);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.project.weekly-progress')
            ->layout('layouts.project', ['title' => 'Input Progress Mingguan']);
    }
}