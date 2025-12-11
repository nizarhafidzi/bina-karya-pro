<?php

namespace App\Livewire\Project;

use App\Models\Project;
use Livewire\Component;

class TerminList extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        // Ambil termins, urutkan agar yang 'submitted' (Nagih) muncul paling atas
        $termins = $this->project->termins()
            ->orderByRaw("FIELD(status, 'submitted', 'ready', 'paid', 'planned')")
            ->get();

        return view('livewire.project.termin-list', [
            'termins' => $termins
        ]);
    }
}