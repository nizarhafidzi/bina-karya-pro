<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\DailyReport;
use App\Models\RabItem;
use Livewire\Component;
use Livewire\WithFileUploads; // Penting untuk upload foto
use Filament\Notifications\Notification; // Kita bisa pakai notif Filament walau di luar panel

class DailyReportInput extends Component
{
    use WithFileUploads;

    public Project $project;

    public $date;
    public $weather_am = 'Cerah';
    public $weather_pm = 'Cerah';
    public $notes;
    public $photos = [];
    
    // BARU: Penampung Item yang dipilih
    public $availableItems = []; 
    public $selectedItems = []; 

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->date = now()->format('Y-m-d');
        
        // Cek Role
        $myRoles = auth()->user()->roles->pluck('name')->toArray();
        if (!count(array_intersect($myRoles, ['Site Manager', 'Tenant Admin', 'Super Admin']))) {
            abort(403, 'Akses Ditolak.');
        }

        // LOAD ITEM YANG SEDANG AKTIF (Berdasarkan Jadwal Minggu Ini)
        // Kita bantu Site Manager dengan hanya menampilkan item yang relevan (misal jadwal minggu ini +/- 2 minggu)
        // Atau tampilkan semua juga boleh kalau itemnya sedikit.
        $this->availableItems = $project->rabItems()
            ->with('ahsMaster') // Ambil nama pekerjaan
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->ahsMaster->name ?? 'Item #'.$item->id,
                ];
            })->toArray();
    }

    public function saveReport()
    {
        $this->validate([
            'date' => 'required|date',
            'weather_am' => 'required|string',
            'weather_pm' => 'required|string',
            'notes' => 'nullable|string',
            'photos.*' => 'image|max:5120',
            'selectedItems' => 'array', // Validasi array
        ]);

        // 1. Simpan Laporan Utama
        $report = DailyReport::create([
            'project_id' => $this->project->id,
            'site_manager_id' => auth()->id(),
            'date' => $this->date,
            'weather_am' => $this->weather_am,
            'weather_pm' => $this->weather_pm,
            'notes' => $this->notes,
        ]);

        // 2. Simpan Relasi Item (Checklist)
        if (!empty($this->selectedItems)) {
            $report->workItems()->attach($this->selectedItems);
        }

        // 3. Simpan Foto (Simulasi)
        if ($this->photos) {
            foreach ($this->photos as $photo) {
                $photo->store('daily-reports', 'public');
            }
        }

        $this->reset(['notes', 'photos', 'selectedItems']); // Reset form
        session()->flash('message', 'Laporan Harian berhasil disimpan.');
        return redirect()->route('project.dashboard', $this->project);
    }

    public function render()
    {
        return view('livewire.project.daily-report-input')
            ->layout('layouts.project', ['title' => 'Input Laporan Harian']);
    }
}