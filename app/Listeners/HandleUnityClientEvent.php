<?php

namespace App\Listeners;

use App\Events\UnityResponseEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Reverb\Events\MessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUnityClientEvent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event)
    {

        // 1. Decode the raw message
        $message = json_decode($event->message, true);
        Log::info('Received WebSocket message:', $message);
        if (! $message || ! isset($message['event'], $message['channel'], $message['data']['message'])) {
            Log::info("G0STO DE TORREMOS");
            return; // Invalid or missing data
        }

        // 2. Check if this is the event we care about
        if ($message['event'] !== 'client-ExampleEvent') {
            Log::info("bunITO HEIN");
            return;
        }

        // 3. Decide based on real time
        $second = now()->second;
        $response = $second % 2 === 1 ? 'yes' : 'no';

        // 4. Send it back to the same channel via custom event
        /*broadcast(new UnityResponseEvent(
            channel: $message['channel'],
            message: $response
        ));*/

        // Step 4: Send response directly to Unity client
        $event->connection->send(json_encode([
            'event' => 'unity-response',
            'data' => [
                'message' => $response,
            ],
        ]));
    }
}
