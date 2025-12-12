<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterLibraryImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            // Sheet 1: Import Daftar Harga Bahan/Upah
            'Resources' => new ResourcesSheetImport(),
            
            // Sheet 2: Import Analisa Harga Satuan (AHS)
            'Analysis'  => new AhsSheetImport(),
        ];
    }
}