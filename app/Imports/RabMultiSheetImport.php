<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\Wbs;
use App\Models\RabItem;
use App\Models\AhsMaster;
use App\Models\ResourcePrice;
use App\Models\RabItemMaterial;
use App\Services\RabCalculatorService;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RabMultiSheetImport implements WithMultipleSheets
{
    protected $projectId;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    public function sheets(): array
    {
        return [
            // Kita fokus di Sheet utama: 'RAB'
            // User bisa menamai sheet 'RAB' atau sheet pertama akan diambil otomatis jika pakai index 0
            0 => new RabSheetImport($this->projectId), 
        ];
    }
}

// --- SUB-CLASS: IMPORT RAB SHEET ---
class RabSheetImport implements ToCollection, WithHeadingRow
{
    protected $projectId;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    public function collection(Collection $rows)
    {
        $project = Project::find($this->projectId);
        if (!$project) return;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Validasi kolom wajib
                // Format Excel: | wbs_name | ahs_code | ahs_name | qty | unit |
                if (empty($row['wbs_name'])) continue;

                // 1. Handle WBS (Find or Create)
                $wbs = Wbs::firstOrCreate(
                    [
                        'project_id' => $this->projectId,
                        'name' => trim($row['wbs_name'])
                    ],
                    [
                        'parent_id' => null, 
                        'sort_order' => 99
                    ]
                );

                // Jika kolom ahs_name/ahs_code kosong, berarti ini cuma Baris Judul WBS (Skip buat item)
                if (empty($row['ahs_name'])) continue;

                // 2. Cari AHS Master
                // Prioritas cari by Code jika ada, kalau tidak by Name
                $ahsQuery = AhsMaster::query();
                if (!empty($row['ahs_code'])) {
                    $ahsQuery->where('code', $row['ahs_code']);
                } else {
                    $ahsQuery->where('name', trim($row['ahs_name']));
                }
                
                // Filter Tenant (Opsional: Global or Tenant's own)
                $tenantId = Filament::getTenant()->id;
                $ahsQuery->where(function($q) use ($tenantId) {
                    $q->where('team_id', $tenantId)->orWhereNull('team_id');
                });

                $ahs = $ahsQuery->first();

                if (!$ahs) continue; // Skip jika AHS tidak ditemukan di database

                // 3. Buat RAB Item
                $rabItem = RabItem::create([
                    'wbs_id' => $wbs->id,
                    'ahs_master_id' => $ahs->id,
                    'qty' => $row['qty'] ?? 1,
                    'unit' => $row['unit'] ?? $ahs->unit ?? 'ls', // Fallback unit
                    'unit_price' => 0,
                    'total_price' => 0
                ]);

                // 4. SNAPSHOT PRICING (Logic Kunci Integritas)
                // Copy-paste dari logic Anda sebelumnya
                $totalUnitPrice = 0;

                foreach ($ahs->coefficients as $coef) {
                    $resourcePrice = ResourcePrice::where('resource_id', $coef->resource_id)
                        ->where('region_id', $project->region_id)
                        ->where(fn($q) => $q->where('team_id', $tenantId)->orWhereNull('team_id'))
                        ->orderBy('team_id', 'desc')->orderBy('year', 'desc')->first();

                    $finalPrice = $resourcePrice ? $resourcePrice->price : $coef->resource->default_price;
                    $subtotal = $coef->coefficient * $finalPrice;

                    RabItemMaterial::create([
                        'rab_item_id' => $rabItem->id,
                        'resource_name' => $coef->resource->name,
                        'unit' => $coef->resource->unit,
                        'coefficient' => $coef->coefficient,
                        'price' => $finalPrice,
                        'subtotal' => $subtotal,
                    ]);

                    $totalUnitPrice += $subtotal;
                }

                // Update Harga Item
                $rabItem->update([
                    'unit_price' => $totalUnitPrice,
                    'total_price' => $totalUnitPrice * $rabItem->qty
                ]);
            }

            // Recalculate Project Total
            (new RabCalculatorService())->calculateProject($project);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}