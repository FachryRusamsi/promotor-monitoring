<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromotorLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The location payload to broadcast.
     *
     * Structure:
     *   user_id, user_name, latitude, longitude,
     *   accuracy, speed, heading, recorded_at, server_at
     */
    public array $location;

    /**
     * Create a new event instance.
     *
     * @param array $location  GPS payload from TrackingController
     */
    public function __construct(array $location)
    {
        $this->location = $location;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Uses a public channel so the admin dashboard can subscribe
     * without per-user private channel authentication overhead.
     * Security is handled by the role middleware on the admin routes.
     *
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('promotor-tracking'),
        ];
    }

    /**
     * The event's broadcast name.
     * Frontend listens via: Echo.channel('promotor-tracking').listen('LocationUpdated', ...)
     */
    public function broadcastAs(): string
    {
        return 'LocationUpdated';
    }

    /**
     * Data to broadcast. Only send the location payload.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return $this->location;
    }
}
