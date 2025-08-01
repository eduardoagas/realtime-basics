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
        // 1) Extrai e valida data
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            $connection->send(json_encode([
                'event' => 'unity-response',
                'data'  => ['message' => 'Missing data payload.']
            ]));
            return;
        }

        // 2) Puxa battle_id de dentro de data
        $battleId = $data['battle_id'] ?? null;
        if (! $battleId) {
            $connection->send(json_encode([
                'event' => 'unity-response',
                'data'  => ['message' => 'Missing battle ID.']
            ]));
            return;
        }

        // 3) Adiciona o usuário na hash da batalha e atualiza sessão
        Redis::hset("battle:{$battleId}:users", $userId, true);
        Redis::hset("session:{$token}", 'battle_instance_id', $battleId);

        // 4) Responde ao cliente com confirmação
        $connection->send(json_encode([
            'event' => 'battle_joined',
            'data'  => ['battle_id' => $battleId]
        ]));
    }
}
