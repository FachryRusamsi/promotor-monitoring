<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Region;
use App\Models\Area;
use App\Models\Outlet;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EdukasiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        
        Event::fake();
        Queue::fake();
        Storage::fake('public');

        $role = Role::create(['name' => 'promotor']);
        $region = Region::create(['name' => 'Jakarta']);
        $area = Area::create(['name' => 'Jaksel', 'region_id' => $region->id]);
        
        $this->outlet = Outlet::create([
            'name' => 'Outlet Test', 
            'area_id' => $area->id, 
            'address' => 'Jl Test', 
            'latitude' => 0, 
            'longitude' => 0
        ]);

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'region_id' => $region->id,
            'area_id' => $area->id,
            'phone' => '08123456789'
        ]);

        Attendance::create([
            'user_id' => $this->user->id,
            'outlet_id' => $this->outlet->id,
            'work_date' => now()->toDateString(),
            'check_in_at' => now(),
            'status' => 'working'
        ]);
    }

    public function test_edukasi_can_be_saved()
    {
        $payload = [
            'jml_edukasi' => 5,
            'location_name' => 'Alun-alun',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'foto_edukasi' => UploadedFile::fake()->image('edukasi.jpg')
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.edukasi.store'), $payload);

        $response->assertStatus(302); // Redirect on success

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'jml_edukasi' => 5,
            'jml_sp' => 0,
            'location_name' => 'Alun-alun'
        ]);
        
        // Assert no transaction details are created for edukasi
        $this->assertDatabaseCount('transaction_details', 0);
    }

    public function test_edukasi_requires_foto_and_jml()
    {
        $payload = [
            'location_name' => 'Alun-alun'
            // Missing jml_edukasi and foto_edukasi
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.edukasi.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['jml_edukasi', 'foto_edukasi']);
    }
}
