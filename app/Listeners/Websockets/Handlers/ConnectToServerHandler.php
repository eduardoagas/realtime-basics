<?php

namespace App\WebSocket\Handlers;

use App\WebSocket\Contracts\HandlesUnityEvent;
use Laravel\Reverb\Contracts\Connection;
use Illuminate\Support\Facades\Redis;

class ConnectToServerHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $characterId = $payload['character_id'] ?? null;

        if (!$characterId) {
            $connection->send(json_encode([
                'event' => 'unity-response',
                'data' => ['message' => 'Character ID is required.']
            ]));
            return;
        }

        Redis::set("unity:{$userId}:token", $token);
        Redis::set("unity:{$userId}:character_id", $characterId);

        $connection->send(json_encode([
            'event' => 'unity-response',
            'data' => ['message' => 'Connected and character registered.']
        ]));
    }
}
