<?php

// app/WebSocket/Handlers/BattleWithMonsterHandler.php

namespace App\WebSocket\Handlers;

use App\WebSocket\Contracts\HandlesUnityEvent;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;

class ShootShotHandler implements HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        // Aqui você poderia validar se pode atirar, consultar redis, cooldown etc.

        $allowed = true; // lógica simplificada

        $connection->send(json_encode([
            'event' => 'shoot-response',
            'data' => ['allowed' => $allowed]
        ]));
    }
}