<?php

namespace App\Imports;

use App\Models\AhsMaster;
use App\Models\AhsCoefficient;
use App\Models\Resource;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AhsSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $tenantId = Filament::getTenant()->id;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Validasi data wajib
                // Excel Columns: ahsp_code | ahsp_name | ahsp_unit | resource_code | coefficient
                if (empty($row['ahsp_code']) || empty($row['resource_code'])) continue;

                // 1. Buat/Cari Header AHS (Parent)
                $ahs = AhsMaster::firstOrCreate(
                    [
                        'team_id' => $tenantId,
                        'code'    => $row['ahsp_code'] // Kunci utama: Kode AHS (misal: AHS-BTM-01)
                    ],
                    [
                        'name' => $row['ahsp_name'],
                        // 'total_price' dihitung belakangan/otomatis
                    ]
                );

                // 2. Cari Resource berdasarkan KODE (yang ada di Sheet 1)
                $resource = Resource::where('code', $row['resource_code'])
                    ->where('team_id', $tenantId)
                    ->first();

                if ($resource) {
                    // 3. Simpan Koefisien (Rumus)
                    AhsCoefficient::updateOrCreate(
                        [
                            'ahs_master_id' => $ahs->id,
                            'resource_id'   => $resource->id,
                        ],
                        [
                            'coefficient' => $row['coefficient']
                        ]
                    );
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}