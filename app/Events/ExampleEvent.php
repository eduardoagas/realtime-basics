<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExampleEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;

    /**
     * Create a new event instance.
     */
    public function __construct(string $message)
    {
        Log::info('Example event created with message: ' . $message);
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('chat'), // Canal público
        ];
    }

    /**
     * Definir o nome do evento
     */
    public function broadcastAs(): string
    {
        return 'ExampleEvent'; // Certifique-se de que esse nome bate com o do Unity!
    }

    /**
     * Dados enviados no WebSocket
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message
        ];
    }
}
