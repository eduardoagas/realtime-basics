<?php

// app/WebSocket/Handlers/BattleWithMonsterHandler.php

namespace App\WebSocket\Handlers;

use App\WebSocket\Contracts\HandlesUnityEvent;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;

class CreateBattleHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $battleId = uniqid('battle_', true);

        // Adiciona o usuário na batalha
        Redis::hset("battle:$battleId:users", $userId, true);

        // Atualiza a session do usuário
        Redis::hset("session:$token", 'battle_instance_id', $battleId);

        // Retorna para o usuário
        $connection->send(json_encode([
            'event' => 'battle_created',
            'data' => [
                'battle_id' => $battleId
            ]
        ]));
    }
}
