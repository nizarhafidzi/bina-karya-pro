<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasterLibraryTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ResourcesTemplateSheet(),
            new AnalysisTemplateSheet(),
        ];
    }
}

// --- SHEET 1: RESOURCES ---
class ResourcesTemplateSheet implements FromArray, WithHeadings, WithTitle
{
    public function array(): array
    {
        // Contoh Data
        return [
            ['MAT-001', 'Semen Portland', 'zak', 'material', 65000],
            ['UP-001', 'Tukang Batu', 'oh', 'labor', 150000],
        ];
    }

    public function headings(): array { return ['code', 'name', 'unit', 'category', 'price']; }
    public function title(): string { return 'Resources'; } // Nama Sheet Wajib Sama
}

// --- SHEET 2: ANALYSIS ---
class AnalysisTemplateSheet implements FromArray, WithHeadings, WithTitle
{
    public function array(): array
    {
        // Contoh Data (Kode resource harus match dengan Sheet 1)
        return [
            ['AHS-001', 'Pasangan Bata Merah', 'm2', 'MAT-001', 0.25], // Semen
            ['AHS-001', 'Pasangan Bata Merah', 'm2', 'UP-001', 0.50],  // Tukang
        ];
    }

    public function headings(): array { return ['ahsp_code', 'ahsp_name', 'ahsp_unit', 'resource_code', 'coefficient']; }
    public function title(): string { return 'Analysis'; } // Nama Sheet Wajib Sama
}