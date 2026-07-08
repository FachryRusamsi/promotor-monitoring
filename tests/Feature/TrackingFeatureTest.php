<?php

namespace Tests\Feature;

use App\Events\PromotorLocationUpdated;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TrackingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_endpoint_stores_in_redis_and_dispatches_event(): void
    {
        // Fake events so we can assert they were dispatched
        Event::fake();

        // Ensure we are using array cache for testing Redis
        Cache::setDefaultDriver('array');

        // Create a promotor user
        $role = Role::factory()->create(['name' => 'promotor']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $payload = [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10.5,
            'recorded_at' => now()->toISOString(),
        ];

        // Send request to tracking endpoint
        $response = $this->actingAs($user)
            ->postJson(route('promotor.tracking.update'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        // Assert Redis (Array Cache in testing) has the latest location
        $cacheKey = "promotor:location:{$user->id}";
        $cachedData = Cache::get($cacheKey);
        
        $this->assertNotNull($cachedData);
        $this->assertEquals(-6.2088, $cachedData['latitude']);
        $this->assertEquals(106.8456, $cachedData['longitude']);
        $this->assertEquals($user->id, $cachedData['user_id']);

        // Assert the history list is appended
        $historyKey = "promotor:location:{$user->id}:history";
        $historyData = Cache::get($historyKey);
        $this->assertIsArray($historyData);
        $this->assertCount(1, $historyData);

        // Assert that the PromotorLocationUpdated event was dispatched
        Event::assertDispatched(PromotorLocationUpdated::class, function ($event) use ($user) {
            return $event->location['user_id'] === $user->id 
                && $event->location['latitude'] === -6.2088;
        });
    }
}
