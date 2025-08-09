<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubProvider;
use Laravel\Reverb\Contracts\Connection;

class UnityConnectionRegistry
{
    protected array $connectionsByUser = [];
    protected PubSubProvider $pubSub;
    protected const REDIS_KEY = 'unity_connections';

    public function __construct(PubSubProvider $pubSub)
    {
        $this->pubSub = $pubSub;
    }

    public function register(string $userId, Connection $connection): void
    {
        Log::info("Registering user $userId with connection ID: " . $connection->id());
        $this->connectionsByUser[$userId] = $connection;

        $connectionData = [
            'connection_id' => $connection->id(),
            'server' => gethostname(),
            'registered_at' => now()->toISOString(),
        ];

        $payload = [
            'action' => 'register',
            'user_id' => $userId,
            'data' => $connectionData,
        ];

        $this->pubSub->publish($payload)
            ->then(function () use ($userId) {
                Log::info("Register event published successfully for user $userId");
            }, function ($error) {
                Log::error("Failed to publish register event: " . $error->getMessage());
            });
    }

    public function get(string $userId): ?Connection
    {
        return $this->connectionsByUser[$userId] ?? null;
    }

    public function remove(Connection $connection): void
    {
        foreach ($this->connectionsByUser as $userId => $conn) {
            if ($conn === $connection) {
                Log::info("Removing connection for user $userId (ID: " . $connection->id() . ")");
                unset($this->connectionsByUser[$userId]);

                $payload = [
                    'action' => 'remove',
                    'user_id' => $userId,
                ];

                $this->pubSub->publish($payload)
                    ->then(function () use ($userId) {
                        Log::info("Remove event published successfully for user $userId");
                    }, function ($error) {
                        Log::error("Failed to publish remove event: " . $error->getMessage());
                    });

                break;
            }
        }
    }

    public function sendToUser(int $userId, array $payload): void
    {
        if (isset($this->connectionsByUser[$userId])) {
            Log::info("sendToUser: User $userId is local. Sending message directly.");
            $this->connectionsByUser[$userId]->send(json_encode($payload));
        } else {
            Log::info("sendToUser: User $userId NOT found locally. Publishing message via PubSub.");
            $pubPayload = [
                'user_id' => $userId,
                'payload' => $payload,
            ];
            $this->pubSub->publish($pubPayload)
                ->then(function () use ($userId) {
                    Log::info("Message published successfully for user $userId");
                }, function ($error) use ($userId) {
                    Log::error("Failed to publish message for user $userId: " . $error->getMessage());
                });
        }
    }

    public function broadcastToUsers(array $userIds, array $payload): void
    {
        foreach ($userIds as $userId) {
            $this->sendToUser((int)$userId, $payload);
        }
    }

    public function getAllLocalUserIds(): array
    {
        return array_keys($this->connectionsByUser);
    }
}
