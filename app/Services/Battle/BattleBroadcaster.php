<?php

namespace App\Services\Battle;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;

class BattleBroadcaster
{
    /**
     * Envia uma mensagem para todos os usuários conectados à batalha.
     *
     * @param string $battleId
     * @param array $payload Dados que serão enviados no broadcast
     * @return void
     */
    public static function broadcastToBattle(string $battleId, array $payload): void
    {
        Log::info("Broadcasting message to battle: $battleId");

        $userIds = Redis::smembers("battle:$battleId:users");

        if (empty($userIds)) {
            Log::warning("No users found to broadcast in battle $battleId.");
            return;
        }

        Log::debug("Users in battle $battleId: " . implode(', ', $userIds));
        Log::debug("Payload to broadcast: " . json_encode($payload));

        UnityConnectionRegistry::broadcastToUsers($userIds, $payload);

        Log::info("Broadcast sent to " . count($userIds) . " users in battle $battleId.");
    }
}
