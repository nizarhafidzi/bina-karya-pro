<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Services\CashFlowService;
use Livewire\Component;

class OwnerFinancialStats extends Component
{
    public Project $project;
    public $stats = [];

    public function mount(Project $project)
    {
        $this->project = $project;
        
        // Panggil Service Logic Khusus Owner
        $service = new CashFlowService();
        $this->stats = $service->getOwnerFinancialSummary($project);
    }

    public function render()
    {
        return view('livewire.project.owner-financial-stats');
    }
}