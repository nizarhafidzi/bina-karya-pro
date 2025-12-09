<?php

namespace App\Services;

use App\Models\RabItem;
use App\Models\Project;

class RabCalculatorService
{
    public function calculateItem(RabItem $item): void
    {
        // Hitung subtotal material
        $unitPrice = $item->materials()->sum('subtotal');

        // Update item ini
        $item->update([
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $item->qty,
        ]);
        
        // Trigger hitung total proyek
        if ($item->wbs && $item->wbs->project) {
            $this->calculateProject($item->wbs->project);
        }
    }

    public function calculateProject(Project $project): void
    {
        // CARA AMAN: Kita hitung manual tanpa HasManyThrough untuk menghindari konflik Join
        // 1. Ambil semua ID WBS milik proyek ini
        $wbsIds = $project->wbs()->pluck('id');
        
        // 2. Jumlahkan total_price semua RabItem yang wbs_id-nya ada di list tadi
        $grandTotal = RabItem::whereIn('wbs_id', $wbsIds)->sum('total_price');

        // 3. Simpan ke tabel projects
        $project->update([
            'contract_value' => $grandTotal
        ]);
    }
}