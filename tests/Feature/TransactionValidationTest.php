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
            'jml_edukasi' => 1,
            'jml_sp' => 1,
            'jml_pulsa' => 0,
            'jml_aktivasi_gemini' => 0,
            'msisdns' => [
                ['number' => '+62 858-1234-5678', 'type' => 'starter_pack'],
                ['number' => '6285812345678', 'type' => 'reload'],
                ['number' => '085812345678', 'type' => 'gemini_activation'],
                ['number' => '85812345678', 'type' => 'starter_pack'], // No zero prefix
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.transactions.store'), $payload);

        $response->assertStatus(302); // Redirect to dashboard on success

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'jml_edukasi' => 1,
        ]);

        // Assert all variations normalized to exactly 085812345678
        $this->assertDatabaseHas('transaction_details', ['msisdn' => '085812345678', 'type' => 'starter_pack']);
        $this->assertDatabaseHas('transaction_details', ['msisdn' => '085812345678', 'type' => 'reload']);
        $this->assertDatabaseHas('transaction_details', ['msisdn' => '085812345678', 'type' => 'gemini_activation']);
        
        $count = \App\Models\TransactionDetail::where('msisdn', '085812345678')->count();
        $this->assertEquals(4, $count, 'All 4 variations should have been normalized to the same MSISDN.');
    }

    public function test_it_rejects_msisdn_if_too_short_after_normalization()
    {
        $payload = [
            'jml_edukasi' => 1,
            'jml_sp' => 1,
            'jml_pulsa' => 0,
            'jml_aktivasi_gemini' => 0,
            'msisdns' => [
                // "123" -> normalized becomes "0123" which is 4 chars (fails min:9)
                ['number' => '+62 123', 'type' => 'starter_pack'], 
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.transactions.store'), $payload);

        // Validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['msisdns.0.number']);
    }

    public function test_it_rejects_msisdn_if_too_long_after_normalization()
    {
        $payload = [
            'jml_edukasi' => 1,
            'jml_sp' => 1,
            'jml_pulsa' => 0,
            'jml_aktivasi_gemini' => 0,
            'msisdns' => [
                // Very long string
                ['number' => '+62 858-1234-5678-9999-9999', 'type' => 'starter_pack'], 
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('promotor.transactions.store'), $payload);

        // Validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['msisdns.0.number']);
    }
}
