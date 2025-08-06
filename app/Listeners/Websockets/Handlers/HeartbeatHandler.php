<?php

// app/WebSocket/Handlers/BattleWithMonsterHandler.php

namespace App\WebSockets\Handlers;

use App\WebSocket\Contracts\HandlesUnityEvent;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;

class HeartbeatHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        Redis::set("unity:{$userId}:last_seen", now()->toDateTimeString());

        $connection->send(json_encode([
            'event' => 'heartbeat-response',
            'data' => ['message' => 'Heartbeat received.']
        ]));
    }
}