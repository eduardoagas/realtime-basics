<?php

namespace App\Listeners\WebSockets;

use App\Models\Character;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Listeners\WebSockets\UnityEventDispatcher;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;
use Laravel\Reverb\Events\MessageReceived;

class HandleUnityClientEvent
{
    protected UnityConnectionRegistry $registry;

    public function __construct(
        protected UnityEventDispatcher $dispatcher,
        UnityConnectionRegistry $registry
    ) {
        $this->registry = $registry;
    }
    public function handle(MessageReceived $event): void
    {
        //Log::debug('[WS RAW IN] conn=' . $event->connection->id() . '  payload=' . $event->message);
        $connection = $event->connection;
        $payload = json_decode($event->message, true);
        $type = $payload['event'] ?? null;
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
        $this->registry->register($userId, $connection);


        $this->dispatcher->dispatch($type, $payload, $userId, $token, $event->connection);
    }


    public function onDisconnect(Connection $connection): void
    {
        $this->registry->remove($connection);
        Log::info("Unity connection disconnected", ['connectionId' => spl_object_id($connection)]);
    }
}
