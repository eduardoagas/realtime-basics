<?php

namespace App\WebSocket\Handlers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\WebSocket\Contracts\HandlesUnityEvent;

class BattleWithMonsterHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $characterJson = collect(Redis::keys("character_session:*"))
            ->map(fn($key) => Redis::hgetall($key)) // ← aqui é hgetall, não get
            ->filter() // remove vazios
            ->firstWhere('user_id', (string) $userId); // cuidado: tudo vira string no Redis

        Log::info("CharacterJson = " . json_encode($characterJson));

        if (!$characterJson) {
            $connection->send(json_encode(['error' => 'Character not found in session']));
            return;
        }
        //musar enemy_id aqui ou:
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
        Redis::sadd("battle:$battleId:users", $userId);
        Redis::hset("battle:$battleId:characters", $characterJson['id'], json_encode($characterJson));
        $monsterIndex = 1; // se for só um inimigo
        //    foreach ($monsters as $index => $monster) {
        //  Redis::hset("battle:$battleId:monsters", $index + 1, json_encode($monster));
        //}
        Redis::hset("battle:$battleId:monsters", (string) $monsterIndex, json_encode($monster));
        Redis::hset("session:$token", 'battle_instance_id', $battleId);

        $connection->send(json_encode([
            'event' => 'battle_with_monster_created',
            'battle_id' => $battleId,
            'character' => [
                $characterJson['id'] => $characterJson
            ],
            'monster' => [
                (string) $monsterIndex => $monster
            ]
        ]));
    }
}
