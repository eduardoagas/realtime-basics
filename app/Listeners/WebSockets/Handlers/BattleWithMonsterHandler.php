<?php

namespace App\Listeners\WebSockets\Handlers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Services\Battle\StaminaService;
use Laravel\Reverb\Contracts\Connection;

class BattleWithMonsterHandler
{
    protected StaminaService $staminaService;

    public function __construct(StaminaService $staminaService)
    {
        $this->staminaService = $staminaService;
    }

    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $characterJson = collect(Redis::keys("character_session:*"))
            ->map(fn($key) => Redis::hgetall($key))
            ->filter()
            ->firstWhere('user_id', (string) $userId);

        Log::info("CharacterJson = " . json_encode($characterJson));

        if (!$characterJson) {
            $connection->send(json_encode(['error' => 'Character not found in session']));
            return;
        }

        // Criar monstros
        $monsters = [];
        //$monsterCount = $payload['monster_count'] ?? 1;
        $monsterCount = 1;
        $battleId = uniqid('battle_', true);
        $characterId = $characterJson['id'];

        Redis::sadd("battle:$battleId:users", $userId);
        Redis::hset("battle:$battleId:characters", $characterId, json_encode($characterJson));
        Redis::hset("session:$token", 'battle_instance_id', $battleId);


        // 🔵 Primeiro loop: Criar e armazenar os monstros
        for ($i = 1; $i < $monsterCount + 1; $i++) {
            $monster = [
                'name' => "Monster #" . ($i),
                'maxhp' => 80,
                'hp' => 80,
                'level' => 1,
                'pattack' => 12,
                'mattack' => 6,
                'defense' => 5,
                'stamina' => rand(5, 10),
                'agility' => rand(0, 300),
            ];

            $monsters[] = $monster;
            Redis::hset("battle:$battleId:monsters", (string) $i, json_encode($monster));
        }
        $now = now()->timestamp;
        // 🟡 Segundo loop: Inicializar stamina dos monstros
        foreach ($monsters as $i => $monster) {
            $monsterStaminaData = $this->staminaService->initializeStamina(
                $now,
                $monster['stamina'],
                $monster['agility']
            );

            Redis::hset("battle:$battleId:stamina_data", "monster:$i", json_encode($monsterStaminaData));
        }

        Redis::hset("battle:$battleId:stamina_data", "monster:$i", json_encode($monsterStaminaData));


        // 🟢 Stamina do personagem
        $characterStaminaData = $this->staminaService->initializeStamina(
            $now,
            (int) $characterJson['stamina'],
            (int) $characterJson['agility']
        );

        Redis::hset("battle:$battleId:stamina_data", "character:$characterId", json_encode($characterStaminaData));

        $connection->send(json_encode([
            'event' => 'unity-response',
            'character' => [
                $characterId => $characterJson
            ],
            'data' => [
                'message' => 'Battle created!',
                'battle_instance' => $battleId,
                'monsters' => $monsters
            ]
        ]));
    }
}
