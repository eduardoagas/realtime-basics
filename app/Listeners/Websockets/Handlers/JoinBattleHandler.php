<?php

// app/WebSocket/Handlers/BattleWithMonsterHandler.php

namespace App\WebSocket\Handlers;

use App\WebSocket\Contracts\HandlesUnityEvent;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;

class JoinBattleHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $battleId = $payload['battle_instance'] ?? null;

        if (!$battleId) {
            $connection->send(json_encode([
                'event' => 'unity-response',
                'data' => ['message' => 'Missing battle instance.']
            ]));
            return;
        }

        Redis::sadd("battle:{$battleId}:players", $userId);

        $connection->send(json_encode([
            'event' => 'unity-response',
            'data' => ['message' => "Joined battle $battleId."]
        ]));
    }
}
