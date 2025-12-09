<?php

namespace Database\Seeders;

use App\Models\AhsMaster;
use App\Models\AhsCoefficient;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\Region;
use App\Models\Resource;
use App\Models\Team;
use App\Models\Wbs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Data Awal (Simulasi Master Data)
        // Pastikan Resource & Region ada (dari seeder sebelumnya)
        $team = Team::where('slug', 'sukses-jaya')->first();
        if (!$team) return;

        $region = Region::first();
        $resource = Resource::first(); // Semen Portland Global

        // Buat Dummy AHS Master (Jika belum ada)
        $ahs = AhsMaster::create([
            'team_id' => null, // Global
            'name' => 'Pekerjaan Beton K-175',
            'code' => 'A.4.1.1.5',
        ]);

        // Buat Koefisien (Resep)
        AhsCoefficient::create([
            'ahs_master_id' => $ahs->id,
            'resource_id' => $resource->id, // Semen
            'coefficient' => 1.2, // Butuh 1.2 Zak
        ]);

        // 2. Buat Project (Milik Tenant)
        $project = Project::create([
            'team_id' => $team->id,
            'name' => 'Pembangunan Ruko 2 Lantai',
            'code' => 'SPK-001/2023',
            'region_id' => $region->id,
            'start_date' => now(),
            'status' => 'draft',
        ]);

        // 3. Buat WBS
        $wbs = Wbs::create([
            'project_id' => $project->id,
            'name' => 'I. Pekerjaan Struktur',
            'sort_order' => 1,
        ]);

        // 4. Simulasi User Input RAB Item (Logic Snapshot Manual disini untuk testing DB)
        // Di aplikasi nyata, ini dilakukan oleh CreateAction::after()
        
        $rabItem = RabItem::create([
            'wbs_id' => $wbs->id,
            'ahs_master_id' => $ahs->id,
            'qty' => 10, // 10 m3
            'unit' => 'm3',
        ]);

        // Trigger Service Calculator (Simulasi Snapshot)
        // Disini kita panggil logic manual agar seeder valid
        $price = $resource->default_price; 
        
        \App\Models\RabItemMaterial::create([
            'rab_item_id' => $rabItem->id,
            'resource_name' => $resource->name,
            'unit' => $resource->unit,
            'coefficient' => 1.2,
            'price' => $price,
            'subtotal' => 1.2 * $price,
        ]);

        // Recalculate
        (new \App\Services\RabCalculatorService)->calculateItem($rabItem);
        (new \App\Services\RabCalculatorService)->calculateProject($project);
    }
}