<?php

// app/WebSocket/Handlers/BattleWithMonsterHandler.php

namespace App\WebSockets\Handlers;

use App\WebSockets\Contracts\HandlesUnityEvent;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;

class ShootHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $session = json_decode(Redis::get("session:$token"), true);
        $battleId = $session['battle_instance_id'] ?? null;

        if (!$battleId || !Redis::exists("battle:$battleId:users")) {
            $connection->send(json_encode([
                'event' => 'unity-response',
                'data' => ['error' => 'Not in a valid battle instance']
            ]));
            return;
        }

        $usersInBattle = Redis::hkeys("battle:$battleId:users");

        foreach ($usersInBattle as $uid) {
            $targetConnection = UnityConnectionRegistry::get($uid);
            if ($targetConnection) {
                $targetConnection->send(json_encode([
                    'event' => 'battle_message',
                    'data' => ['message' => "User $userId shot!"]
                ]));
            }
        }

        $connection->send(json_encode([
            'event' => 'unity-response',
            'data' => ['message' => 'Shot propagated to all users in battle.']
        ]));
    }
}
