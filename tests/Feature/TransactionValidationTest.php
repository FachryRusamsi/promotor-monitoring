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

class TransactionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        
        Event::fake();
        Queue::fake();

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

        // Create an attendance for today so outlet_id resolves correctly
        Attendance::create([
            'user_id' => $this->user->id,
            'outlet_id' => $this->outlet->id,
            'work_date' => now()->toDateString(),
            'check_in_at' => now(),
            'status' => 'working'
        ]);
    }

    public function test_msisdn_is_normalized_correctly_and_saved()
    {
        $payload = [
            'msisdn' => '+62 858-1234-5678',
            'type' => 'starter_pack',
            'location_name' => 'Outlet Cabang Test',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'foto_penjualan' => UploadedFile::fake()->image('penjualan.jpg')
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.transactions.store'), $payload);

        $response->assertStatus(302); // Redirect to dashboard on success

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'jml_sp' => 1,
            'location_name' => 'Outlet Cabang Test'
        ]);

        // Assert MSISDN is normalized
        $this->assertDatabaseHas('transaction_details', [
            'msisdn' => '085812345678', 
            'type' => 'starter_pack'
        ]);
    }

    public function test_it_rejects_msisdn_if_too_short_after_normalization()
    {
        $payload = [
            'msisdn' => '+62 123', // "123" -> normalized becomes "0123" which is 4 chars (fails min:9)
            'type' => 'starter_pack',
            'location_name' => 'Outlet Cabang Test',
            'foto_penjualan' => UploadedFile::fake()->image('penjualan.jpg')
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.transactions.store'), $payload);

        // Validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['msisdn']);
    }

    public function test_it_rejects_msisdn_if_too_long_after_normalization()
    {
        $payload = [
            'msisdn' => '+62 858-1234-5678-9999-9999', // Very long string
            'type' => 'starter_pack',
            'location_name' => 'Outlet Cabang Test',
            'foto_penjualan' => UploadedFile::fake()->image('penjualan.jpg')
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.transactions.store'), $payload);

        // Validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['msisdn']);
    }

    public function test_it_rejects_msisdn_if_wrong_prefix()
    {
        $payload = [
            'msisdn' => '08112345678', // 0811 is Telkomsel prefix, should fail regex
            'type' => 'starter_pack',
            'location_name' => 'Outlet Cabang Test',
            'foto_penjualan' => UploadedFile::fake()->image('penjualan.jpg')
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.transactions.store'), $payload);

        // Validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['msisdn']);
    }
}
