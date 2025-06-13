<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnityResponseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $channel, public string $message) {}

    public function broadcastOn(): Channel
    {
        return new Channel($this->channel);
    }

    public function broadcastAs()
    {
        return 'unity-response';
    }

    public function broadcastWith()
    {
        return ['message' => $this->message];
    }
}
