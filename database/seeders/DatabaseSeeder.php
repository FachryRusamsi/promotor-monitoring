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

        // Seed Regions and Branches
        $regionsData = [
            'Central Java' => ['Semarang', 'Sleman', 'Solo', 'Yogyakarta', 'Purwokerto'],
            'East Java' => ['Jember', 'Malang', 'Surabaya', 'Sidoarjo'],
            'Bali Nusa Tenggara' => ['Denpasar', 'Gianyar', 'Singaraja'],
        ];

        $firstArea = null;
        $firstRegion = null;
        
        $phoneCounter = 1000;

        foreach ($regionsData as $regionName => $areas) {
            $region = Region::create(['name' => $regionName]);
            if (!$firstRegion) $firstRegion = $region;

            foreach ($areas as $areaName) {
                $area = Area::create(['name' => $areaName, 'region_id' => $region->id]);
                if (!$firstArea) $firstArea = $area;

                // Create 1 Outlet for the area
                $outlet = \App\Models\Outlet::create([
                    'area_id' => $area->id,
                    'name' => 'Outlet ' . $areaName,
                    'address' => 'Jl. Dummy ' . $areaName,
                    'latitude' => -7.250445,
                    'longitude' => 112.768845,
                ]);

                // Create 2 Promotors for each area
                for ($i = 1; $i <= 2; $i++) {
                    $promotor = User::create([
                        'name' => 'Promotor ' . $areaName . ' ' . $i,
                        'email' => 'promotor.' . strtolower($areaName) . $i . '@test.com',
                        'phone' => '0822' . str_pad($phoneCounter++, 8, '0', STR_PAD_LEFT),
                        'password' => Hash::make('password'),
                        'role_id' => $promotorRole->id,
                        'region_id' => $region->id,
                        'area_id' => $area->id,
                    ]);

                    // Seed Attendance for today
                    $attendance = \App\Models\Attendance::create([
                        'user_id' => $promotor->id,
                        'outlet_id' => $outlet->id,
                        'work_date' => today(),
                        'check_in_at' => now()->startOfDay()->addHours(8)->addMinutes(rand(0, 30)),
                        'check_out_at' => now()->startOfDay()->addHours(17)->addMinutes(rand(0, 30)),
                        'status' => 'finished',
                        'check_in_lat' => $outlet->latitude + (rand(-10, 10) / 10000),
                        'check_in_lng' => $outlet->longitude + (rand(-10, 10) / 10000),
                    ]);

                    // Seed Transaction for Edukasi
                    \App\Models\Transaction::create([
                        'user_id' => $promotor->id,
                        'outlet_id' => $outlet->id,
                        'attendance_id' => $attendance->id,
                        'transaction_date' => today(),
                        'jml_edukasi' => rand(5, 20),
                        'jml_sp' => 0,
                        'jml_pulsa' => 0,
                        'jml_aktivasi_gemini' => 0,
                        'validation_status' => 'valid',
                    ]);

                    // Seed Transaction for Penjualan
                    $transaction = \App\Models\Transaction::create([
                        'user_id' => $promotor->id,
                        'outlet_id' => $outlet->id,
                        'attendance_id' => $attendance->id,
                        'transaction_date' => today(),
                        'jml_edukasi' => 0,
                        'jml_sp' => rand(2, 10),
                        'jml_pulsa' => rand(10, 50),
                        'jml_aktivasi_gemini' => rand(0, 5),
                        'validation_status' => 'valid',
                    ]);

                    // Seed Transaction Details (MSISDNs) for Penjualan
                    for ($j = 0; $j < rand(1, 3); $j++) {
                        \App\Models\TransactionDetail::create([
                            'transaction_id' => $transaction->id,
                            'msisdn' => '0815' . rand(10000000, 99999999),
                            'type' => 'reload',
                            'notes' => 'Dummy sale',
                        ]);
                    }
                }
            }
        }

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'phone' => '08111111111',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'region_id' => $firstRegion->id,
            'area_id' => $firstArea->id,
        ]);
    }
}
