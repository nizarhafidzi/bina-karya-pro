<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\DailyLog;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectGallery extends Component
{
    use WithPagination;

    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        // Ambil Log yang punya foto, urutkan tanggal terbaru
        $logs = DailyLog::where('project_id', $this->project->id)
            ->whereHas('images')
            ->with('images')
            ->orderBy('date', 'desc')
            ->paginate(5); // Pagination per hari laporan

        return view('livewire.project.project-gallery', [
            'logs' => $logs
        ]);
    }
}