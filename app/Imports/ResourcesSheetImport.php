<?php

namespace App\Imports;

use App\Models\Resource;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ResourcesSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $tenantId = Filament::getTenant()->id;

        foreach ($rows as $row) {
            // Validasi kolom wajib (Code & Price adalah kunci)
            if (empty($row['code']) || !isset($row['price'])) continue;

            Resource::updateOrCreate(
                [
                    'team_id' => $tenantId, // Scope Tenant
                    'code'    => $row['code'], // Kunci unik berdasarkan Kode (misal: MAT-001)
                ],
                [
                    'name'          => $row['name'],
                    'unit'          => $row['unit'],
                    'category'      => strtolower($row['category'] ?? 'material'), // material/labor/equipment
                    'default_price' => $row['price']
                ]
            );
        }
    }
}