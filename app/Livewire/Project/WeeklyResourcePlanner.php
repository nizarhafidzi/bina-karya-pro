<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\WeeklyResourcePlan;
use App\Services\ResourcePlanningService;
use Filament\Notifications\Notification;
use Livewire\Component;

class WeeklyResourcePlanner extends Component
{
    public Project $project;
    
    // State Filter
    public $selectedWeek = 1;
    public $weekOptions = [];

    // State Data Table (Untuk Binding Input)
    // Format: $plans[resource_plan_id] = adjusted_qty
    public $adjustments = []; 

    public function mount(Project $project)
    {
        $this->project = $project;
        
        // Generate Opsi Minggu (Misal sampai Minggu 52 atau Max Jadwal)
        $maxWeek = $project->projectSchedules()->max('week') ?? 24;
        for ($i = 1; $i <= $maxWeek; $i++) {
            $this->weekOptions[$i] = "Minggu ke-{$i}";
        }
    }

    public function render()
    {
        // Ambil Data Plan untuk Minggu yang dipilih
        $plans = WeeklyResourcePlan::where('project_id', $this->project->id)
            ->where('week', $this->selectedWeek)
            ->with('resource') // Eager load nama bahan
            ->get();

        // Inisialisasi input adjustments dengan nilai yg ada di DB
        foreach ($plans as $plan) {
            // Jika belum ada di array state, masukkan
            if (!array_key_exists($plan->id, $this->adjustments)) {
                // Tampilkan adjusted_qty jika ada, jika null kosongkan (biar placeholder 'System' terlihat)
                $this->adjustments[$plan->id] = $plan->adjusted_qty; 
            }
        }

        return view('livewire.project.weekly-resource-planner', [
            'plans' => $plans
        ]);
    }

    // Fungsi Update saat user mengetik (Blur event)
    public function updateQty($planId, $value)
    {
        $plan = WeeklyResourcePlan::find($planId);
        
        if ($plan) {
            // Jika value kosong, set null (kembali ke system)
            $val = ($value === '' || $value === null) ? null : (float) $value;
            
            $plan->update(['adjusted_qty' => $val]);
            
            Notification::make()
                ->title('Stok Disimpan')
                ->success()
                ->send();
        }
    }

    // Action: Hitung Ulang (Panggil Service Phase 7.0)
    public function recalculate()
    {
        try {
            $service = new ResourcePlanningService();
            $service->generateWeeklyPlan($this->project);
            
            // Reset adjustments UI karena data base mungkin berubah
            $this->reset('adjustments');
            
            Notification::make()
                ->title('Kalkulasi Ulang Berhasil')
                ->body('Kebutuhan resource telah diperbarui berdasarkan jadwal terkini.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }
}