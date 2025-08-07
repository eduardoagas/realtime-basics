<?php

// app/WebSocket/Handlers/BattleWithMonsterHandler.php

namespace App\Listeners\WebSockets\Handlers;

use App\Listeners\WebSockets\Contracts\HandlesUnityEvent;
use Laravel\Reverb\Contracts\Connection;

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
