<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\RabItem;
use Livewire\Component;

class ProjectGanttChart extends Component
{
    public Project $project;
    public $items = [];
    public $maxWeek = 0;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadData();
    }

    public function loadData()
    {
        // 1. Ambil Item RAB yang memiliki jadwal
        // Kita urutkan berdasarkan minggu mulai agar tangga Gantt-nya rapi menurun
        $this->items = RabItem::whereHas('wbs', fn($q) => $q->where('project_id', $this->project->id))
            ->whereNotNull('start_week')
            ->orderBy('start_week')
            ->with('ahsMaster') // Untuk nama pekerjaan
            ->get();

        // 2. Cari Minggu Terakhir untuk menentukan lebar Grid
        $maxEnd = $this->items->max('end_week');
        
        // Kita lebihkan sedikit (buffer) atau minimal 12 minggu agar tampilan tidak gepeng
        $this->maxWeek = max($maxEnd ?? 0, 12);
    }

    public function render()
    {
        return view('livewire.project.project-gantt-chart');
    }
}