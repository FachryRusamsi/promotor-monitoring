<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('name', 'admin')->first();
        $promotorRole = Role::where('name', 'promotor')->first();

        $region = Region::create(['name' => 'Jakarta']);
        $area = Area::create(['name' => 'Jakarta Selatan', 'region_id' => $region->id]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'phone' => '08111111111',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'region_id' => $region->id,
            'area_id' => $area->id,
        ]);

        User::create([
            'name' => 'Promotor User',
            'email' => 'promotor@test.com',
            'phone' => '08222222222',
            'password' => Hash::make('password'),
            'role_id' => $promotorRole->id,
            'region_id' => $region->id,
            'area_id' => $area->id,
        ]);
    }
}
