<?php

namespace App\Listeners\WebSocketHandlers;

use App\Events\Example;
use App\Events\ExampleEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

class ChatMessageHandler
{
    public function handle($connection, $message)
    {
        // Decode the incoming WebSocket message
        $data = json_decode($message, true);

        // Check if the message is for the "chat" channel and has the "ExampleEvent" event
        if (isset($data['channel']) && $data['channel'] === 'chat' && isset($data['event']) && $data['event'] === 'ExampleEvent') {
            // Extract the message data
            $messageData = $data['data']['message'] ?? null;

            if ($messageData) {
                // Log the incoming message
                Log::info('Received WebSocket message:', $data);

                // Manually dispatch the Example event
                broadcast(new ExampleEvent($messageData));
            }
        }
    }
}
