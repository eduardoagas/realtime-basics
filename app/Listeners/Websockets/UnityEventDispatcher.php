<?php
// app/WebSocket/UnityEventDispatcher.php

namespace App\WebSocket;

use App\WebSocket\Contracts\HandlesUnityEvent;
use Laravel\Reverb\Contracts\Connection;
use Illuminate\Support\Str;

class UnityEventDispatcher
{
    protected array $handlers;

    public function __construct()
    {
        $this->handlers = [
            'create_battle' => \App\WebSocket\Handlers\CreateBattleHandler::class,
            'join_battle' => \App\WebSocket\Handlers\JoinBattleHandler::class,
            'shoot' => \App\WebSocket\Handlers\ShootHandler::class,
            'client-shootRequest' => \App\WebSocket\Handlers\ShootShotHandler::class,
            'connect_to_server' => \App\WebSocket\Handlers\ConnectToServerHandler::class,
            'battle_with_monster' => \App\WebSocket\Handlers\BattleWithMonsterHandler::class,
            'use_skill' => \App\WebSocket\Handlers\UseSkillHandler::class,
            // adicionar os demais aqui...
        ];
    }

    public function dispatch(string $event, array $payload, int $userId, string $token, Connection $connection): void
    {
        $handlerClass = $this->handlers[$event] ?? null;

        if (!$handlerClass || !class_exists($handlerClass)) {
            $connection->send(json_encode(['error' => 'Invalid message type']));
            return;
        }

        /** @var HandlesUnityEvent $handler */
        $handler = app($handlerClass);
        $handler->handle($payload, $userId, $token, $connection);
    }
}
