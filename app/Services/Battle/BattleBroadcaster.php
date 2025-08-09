<?php

namespace App\Services\Battle;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Services\UnityConnectionRegistry;

class BattleBroadcaster
{
    protected UnityConnectionRegistry $registry;

    public function __construct(UnityConnectionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Envia uma mensagem para todos os usuários conectados à batalha.
     *
     * @param string $battleId
     * @param array $payload Dados que serão enviados no broadcast
     * @return void
     */
    public function broadcastToBattle(string $battleId, array $payload): void
    {
        Log::info("Broadcasting message to battle: $battleId");

        $userIds = Redis::smembers("battle:$battleId:users");

        if (empty($userIds)) {
            Log::warning("No users found to broadcast in battle $battleId.");
            return;
        }

        Log::debug("Users in battle $battleId: " . implode(', ', $userIds));
        Log::debug("Payload to broadcast: " . json_encode($payload));

        $this->registry->broadcastToUsers($userIds, $payload);

        Log::info("Broadcast sent to " . count($userIds) . " users in battle $battleId.");
    }
}
