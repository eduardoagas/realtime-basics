<?php

// app/WebSocket/Contracts/HandlesUnityEvent.php

namespace App\WebSocket\Contracts;

use Laravel\Reverb\Contracts\Connection;

interface HandlesUnityEvent
{
    public function handle(array $payload, int $userId, string $token, Connection $connection): void;
}
