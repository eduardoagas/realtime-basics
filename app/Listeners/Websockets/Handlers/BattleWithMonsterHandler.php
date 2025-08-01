<?php

// app/WebSocket/Handlers/BattleWithMonsterHandler.php

namespace App\WebSocket\Handlers;

use App\WebSocket\Contracts\HandlesUnityEvent;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;

class BattleWithMonsterHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $characterJson = collect(Redis::keys("character_session:*"))
            ->map(fn($key) => Redis::get($key))
            ->filter()
            ->map(fn($json) => json_decode($json, true))
            ->firstWhere('user_id', $userId);

        if (!$characterJson) {
            $this->sendToUser($userId, ['error' => 'Character not found in session']);
            return;
        }

        $monster = [
            'name' => 'Goblin',
            'maxhp' => 80,
            'hp' => 80,
            'level' => 1,
            'pattack' => 12,
            'mattack' => 6,
            'defense' => 5,
            'agility' => 4,
            'stamina' => 10,
        ];

        $battleId = uniqid('battle_', true);

        Redis::set("battle:$battleId:character", json_encode($characterJson));
        Redis::set("battle:$battleId:monster", json_encode($monster));
        Redis::hset("session:$token", 'battle_instance_id', $battleId);

        $this->sendToUser($userId, [
            'event' => 'battle_with_monster_created',
            'battle_id' => $battleId,
            'character' => $characterJson,
            'monster' => $monster
        ]);
    }

    private function sendToUser(int $userId, array $payload): void
    {
        $connection = UnityConnectionRegistry::get($userId);
        if ($connection) {
            $connection->send(json_encode($payload));
        }
    }
}
