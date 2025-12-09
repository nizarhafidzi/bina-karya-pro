<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\Team;
use App\Models\Region;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {

        // 0. SETUP REGIONS (System Data)
        $regions = [
            ['name' => 'DKI Jakarta', 'code' => 'JKT'],
            ['name' => 'Jawa Barat', 'code' => 'JBR'],
            ['name' => 'Jawa Timur', 'code' => 'JTM'],
        ];

        foreach ($regions as $region) {
            // Region tidak punya team_id (Global)
            Region::firstOrCreate(['code' => $region['code']], $region);
        }
        // 1. INPUT DATA GLOBAL (SNI/Pusat)
        // team_id = NULL
        $globals = [
            ['name' => 'Semen Portland (SNI Global)', 'unit' => 'zak', 'category' => 'material', 'default_price' => 65000],
            ['name' => 'Pasir Beton (SNI Global)', 'unit' => 'm3', 'category' => 'material', 'default_price' => 250000],
            ['name' => 'Pekerja (SNI Global)', 'unit' => 'OH', 'category' => 'labor', 'default_price' => 120000],
        ];

        foreach ($globals as $item) {
            Resource::create(array_merge($item, ['team_id' => null]));
        }

        // 2. INPUT DATA CUSTOM UNTUK TEAM ID 1
        // Kita cari Team ID 1 (Pasti ada karena dibuat di DatabaseSeeder sebelumnya)
        $team = Team::find(1);

        if ($team) {
            $customs = [
                ['name' => 'Semen Tiga Roda (Custom PT Sukses)', 'unit' => 'zak', 'category' => 'material', 'default_price' => 68000],
                ['name' => 'Granit Mewah (Custom PT Sukses)', 'unit' => 'm2', 'category' => 'material', 'default_price' => 185000],
            ];

            foreach ($customs as $item) {
                Resource::create(array_merge($item, ['team_id' => $team->id]));
            }
        }
    }
}