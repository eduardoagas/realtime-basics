<?php

namespace App\Listeners;

use App\Events\UnityResponseEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Reverb\Events\MessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Reverb\Contracts\Connection;

class HandleUnityClientEvent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }


    public static array $connectionsByUser = [];

    public function handle(MessageReceived $event): void
    {
        //TODO: handle also last req of some handlers with maybe maximum of 1sec?

        $data = json_decode($event->message, true);

        $token = $data['token'] ?? null;
        if (!$token || !Redis::exists("session:$token")) {
            $event->connection->send(json_encode(['error' => 'Unauthorized']));
            return;
        }

        $session = json_decode(Redis::get("session:$token"), true);
        $userId = $session['user_id'];
        $username = $session['username'];

        // Salva a conexão na memória
        self::$connectionsByUser[$userId] = $event->connection;

        $type = $data['event'] ?? null;

        match ($type) {
            'create_battle' => $this->createBattle($userId, $token),
            'join_battle' => $this->joinBattle($userId, $data['battle_id'], $token),
            'shoot' => $this->handleShoot($userId, $username, $token),
            'client-ShootRequest' => $this->handleExample($event),
            default => $event->connection->send(json_encode(['error' => 'Invalid message type']))
        };
    }

    private function createBattle(int $userId, string $token): void
    {
        $battleId = uniqid('battle_', true);

        Redis::hset("battle:$battleId:users", $userId, true);
        Redis::hset("session:$token", 'battle_instance_id', $battleId);

        $this->sendToUser($userId, ['event' => 'battle_created', 'battle_id' => $battleId]);
    }

    private function joinBattle(int $userId, string $battleId, string $token): void
    {
        Redis::hset("battle:$battleId:users", $userId, true);
        Redis::hset("session:$token", 'battle_instance_id', $battleId);

        $this->sendToUser($userId, ['event' => 'battle_joined', 'battle_id' => $battleId]);
    }

    private function handleShoot(int $userId, string $username, string $token): void
    {
        $session = json_decode(Redis::get("session:$token"), true);
        $battleId = $session['battle_instance_id'] ?? null;

        if (!$battleId || !Redis::exists("battle:$battleId:users")) {
            $this->sendToUser($userId, ['error' => 'Not in a valid battle instance']);
            return;
        }

        $usersInBattle = Redis::hkeys("battle:$battleId:users");

        foreach ($usersInBattle as $uid) {
            if (isset(self::$connectionsByUser[$uid])) {
                self::$connectionsByUser[$uid]->send(json_encode([
                    'event' => 'battle_message',
                    'message' => "$username shot!",
                ]));
            }
        }
    }

    private function sendToUser(int $userId, array $payload): void
    {
        if (isset(self::$connectionsByUser[$userId])) {
            self::$connectionsByUser[$userId]->send(json_encode($payload));
        }
    }

    public function onDisconnect(Connection $connection): void
    {
        // Future implementation: Clean up self::$connectionsByUser and Redis
    }


    public function handleExample(MessageReceived $event)
    {
        // Verifica lógica de permissão (ex: cooldown, status)
        /*if ($sessionData['status'] !== 'alive') {
        $event->connection->send(json_encode([
            'event' => 'shoot-denied',
            'data' => ['message' => "You can't shoot right now."]
        ]));
        return;
        }*/

        // Registra no "campo comum"
        //BattleRegistry::addMessage($battleId, "$username shot!");

        $event->connection->send(json_encode([
            'event' => 'authorized',
            'data' => ['message' => 'Shoot shot']
        ]));
        return;
    }
}
