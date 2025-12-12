<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\DailyLog;
use App\Models\WeeklyResourcePlan;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Carbon;


class DailyLogInput extends Component
{
    use WithFileUploads;

    public Project $project;
    
    // Form Inputs
    public $date;
    public $weather_am = 'Cerah';
    public $weather_pm = 'Cerah';
    public $manpower_total = 0;
    public $material_note;
    public $problem_note;
    public $work_note;
    public $photos = [];

    // Planning Data (Untuk Referensi)
    public $plannedManpower = 0;
    public $plannedMaterials = [];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->date = now()->format('Y-m-d');
        
        $this->loadPlanningData();
    }

    // Logic: Ambil Plan Resource Minggu Ini
    public function loadPlanningData()
    {
        // 1. Tentukan Minggu Ke-berapa sekarang
        // Asumsi: Start Date project ada, jika tidak pakai created_at
        $start = $this->project->start_date 
            ? Carbon::parse($this->project->start_date) 
            : $this->project->created_at;
            
        $diffInDays = $start->diffInDays(now());
        $currentWeek = ceil(($diffInDays + 1) / 7);

        // 2. Ambil Data Plan dari Phase 7.1
        $plans = WeeklyResourcePlan::where('project_id', $this->project->id)
            ->where('week', $currentWeek)
            ->with('resource')
            ->get();

        // 3. Pisahkan Manpower dan Material
        // Asumsi: Resource dengan satuan 'OH' (Orang Hari) adalah Manpower
        foreach ($plans as $plan) {
            if (strtoupper($plan->unit) === 'OH' || stripos($plan->resource->name, 'tukang') !== false) {
                // Ambil nilai Adjusted (Final) jika ada, kalau tidak System
                $this->plannedManpower += $plan->final_qty;
            } else {
                $this->plannedMaterials[] = [
                    'name' => $plan->resource->name,
                    'qty' => $plan->final_qty,
                    'unit' => $plan->unit
                ];
            }
        }
        
        // Konversi Manpower mingguan ke harian (rata-rata dibagi 7 atau 6 hari kerja)
        // Kita tampilkan apa adanya sebagai "Budget Mingguan"
        $this->plannedManpower = round($this->plannedManpower); 
    }

    public function save()
    {
        $this->validate([
            'date' => 'required|date',
            'manpower_total' => 'required|integer|min:0',
            'work_note' => 'required|string',
            'photos.*' => 'image|max:10240', // Max 10MB
        ]);

        // 1. Simpan Log
        $log = DailyLog::create([
            'project_id' => $this->project->id,
            'site_manager_id' => auth()->id(),
            'date' => $this->date,
            'weather_am' => $this->weather_am,
            'weather_pm' => $this->weather_pm,
            'manpower_total' => $this->manpower_total,
            'work_note' => $this->work_note,
            'material_note' => $this->material_note,
            'problem_note' => $this->problem_note,
        ]);

        // 2. Simpan Foto
        foreach ($this->photos as $photo) {
            $path = $photo->store('project-photos/' . $this->project->id, 'public');
            
            $log->images()->create([
                'path' => $path,
                'category' => 'progress',
                'caption' => 'Dokumentasi ' . $this->date
            ]);
        }

        session()->flash('message', 'Laporan Harian berhasil disimpan.');
        
        // Reset Form
        $this->reset(['photos', 'work_note', 'material_note', 'problem_note']);
        $this->manpower_total = 0;
    }

    public function render()
    {
        return view('livewire.project.daily-log-input')
            ->layout('layouts.project', ['title' => 'Input Laporan Harian']);
    }
}