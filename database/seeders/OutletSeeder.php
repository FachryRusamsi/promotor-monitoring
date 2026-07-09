<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    public function run(): void
    {
        $area = Area::first();
        
        if ($area) {
            Outlet::create([
                'id' => 1,
                'area_id' => $area->id,
                'name' => 'Outlet Indosat Ooredoo',
                'address' => 'Jl. Medan Merdeka Barat',
                'latitude' => -6.1731,
                'longitude' => 106.8228,
            ]);
        }
    }
}
