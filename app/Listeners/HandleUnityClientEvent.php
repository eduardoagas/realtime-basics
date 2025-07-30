<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use Laravel\Reverb\Events\MessageReceived;
use App\Services\UnityConnectionRegistry;

class HandleUnityClientEvent
{
    //NÃO NOMEAR OS MÉTODOS DESSA CLASSE COM HANDLE

    ///** @var array<int, string> Última mensagem processada por conexão */
    //private static array $lastMessageByConnection = [];

    public function handle(MessageReceived $event): void
    {
        //Log::debug('[WS RAW IN] conn=' . $event->connection->id() . '  payload=' . $event->message);
        $connection = $event->connection;
        //$connId  = $event->connection->id();
        $payload = json_decode($event->message, true);
        /* $data  = @json_decode($event->message, true);
        if (
            ! is_array($data) ||
            ($data['channel'] ?? null) !== 'none'
        ) {
            return;
        }*/
        // ————————————————
        // 1) Debounce: ignora se for EXATAMENTE a mesma mensagem da última vez
        /*if (
            isset(self::$lastMessageByConnection[$connId])
            && self::$lastMessageByConnection[$connId] === $payload
        ) {
            return;
        }

        // registra para a próxima comparação
        self::$lastMessageByConnection[$connId] = $payload;
        // ————————————————*/
        $type = $payload['event'] ?? null;

        /*$allowed = ['create_battle', 'join_battle', 'shoot', 'client-shootRequest'];
        if (! in_array($type, $allowed, true)) {
            return;
        }*/

        $token = $payload['token'] ?? null;
        if (!$token) {
            $connection->send(json_encode(['error' => 'Missing token']));
            return;
        }

        $userId = Redis::hget("session:$token", 'user_id');
        if (!$userId) {
            $connection->send(json_encode(['error' => 'Invalid token']));
            return;
        }

        // Registra ou sobrescreve conexão
        UnityConnectionRegistry::register($userId, $connection);



        match ($type) {
            'create_battle' => $this->createBattle($userId, $payload['enemy_id'] ?? null, $token),
            'join_battle' => $this->joinBattle($userId, $payload['battle_id'], $token),
            'shoot' => $this->Shoot($userId, $token),
            'client-shootRequest' => $this->shootShot($event),
            default => $connection->send(json_encode(['error' => 'Invalid message type']))
        };
    }

    private function createBattle(int $userId, ?string $enemyId, string $token): void
    {
        $battleId = uniqid('battle_', true);

        Redis::hset("battle:$battleId:users", $userId, true);
        Redis::hset("session:$token", 'battle_instance_id', $battleId);

        $this->sendToUser($userId, [
            'event' => 'battle_created',
            'battle_id' => $battleId
        ]);
    }

    private function joinBattle(int $userId, string $battleId, string $token): void
    {
        Redis::hset("battle:$battleId:users", $userId, true);
        Redis::hset("session:$token", 'battle_instance_id', $battleId);

        $this->sendToUser($userId, [
            'event' => 'battle_joined',
            'battle_id' => $battleId
        ]);
    }

    private function Shoot(int $userId, string $token): void
    {
        $session = json_decode(Redis::get("session:$token"), true);
        $battleId = $session['battle_instance_id'] ?? null;

        if (!$battleId || !Redis::exists("battle:$battleId:users")) {
            $this->sendToUser($userId, ['error' => 'Not in a valid battle instance']);
            return;
        }

        $usersInBattle = Redis::hkeys("battle:$battleId:users");

        foreach ($usersInBattle as $uid) {
            $targetConnection = UnityConnectionRegistry::get($uid);
            if ($targetConnection) {
                $targetConnection->send(json_encode([
                    'event' => 'battle_message',
                    'message' => "User $userId shot!"
                ]));
            }
        }
    }

    private function sendToUser(int $userId, array $payload): void
    {
        $connection = UnityConnectionRegistry::get($userId);
        if ($connection) {
            $connection->send(json_encode($payload));
        }
    }

    public function shootShot(MessageReceived $event)
    {
        // Exemplo de resposta básica
        UnityConnectionRegistry::dump();
        $event->connection->send(json_encode([
            'event' => 'itpassed',
            'data'  => ['message' => 'Shoot shot'],
        ]));
    }

    public function onDisconnect(Connection $connection): void
    {
        UnityConnectionRegistry::remove($connection);
        Log::info("Unity connection disconnected", ['connectionId' => spl_object_id($connection)]);
    }
}
