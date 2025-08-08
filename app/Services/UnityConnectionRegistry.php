<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;

class UnityConnectionRegistry
{
    protected static array $connectionsByUser = [];
    protected const REDIS_KEY = 'unity_connections';
    protected const REDIS_CHANNEL = 'unity:messages';

    public static function register(string $userId, Connection $connection): void
    {
        Log::info("Registering user $userId with connection ID: " . $connection->id());

        // Cache local
        self::$connectionsByUser[$userId] = $connection;

        // Salva metadados no Redis
        Redis::hset(self::REDIS_KEY, $userId, json_encode([
            'connection_id' => $connection->id(),
            'server' => gethostname(),
            'registered_at' => now()->toISOString()
        ]));
    }

    public static function get(string $userId): ?Connection
    {
        return self::$connectionsByUser[$userId] ?? null;
    }

    public static function remove(Connection $connection): void
    {
        foreach (self::$connectionsByUser as $userId => $conn) {
            if ($conn === $connection) {
                Log::info("Removing connection for user $userId (ID: " . $connection->id() . ")");
                unset(self::$connectionsByUser[$userId]);

                // Remove do Redis
                Redis::hdel(self::REDIS_KEY, $userId);
                break;
            }
        }
    }

    public static function sendToUser(int $userId, array $payload): void
    {
        if (isset(self::$connectionsByUser[$userId])) {
            // Conexão local → envia direto
            self::$connectionsByUser[$userId]->send(json_encode($payload));
        } else {
            // Não está local → publica no Redis para outra instância entregar
            Log::info("sendToUser: Publishing message for remote user $userId via Redis.");
            Redis::publish(self::REDIS_CHANNEL, json_encode([
                'user_id' => $userId,
                'payload' => $payload
            ]));
        }
    }

    public static function broadcastToUsers(array $userIds, array $payload): void
    {
        foreach ($userIds as $userId) {
            self::sendToUser((int)$userId, $payload);
        }
    }

    public static function all(): array
    {
        Log::info("Current local connections: " . json_encode(array_keys(self::$connectionsByUser)));
        $allConnections = Redis::hgetall(self::REDIS_KEY);
        return [
            'local' => self::$connectionsByUser,
            'global' => $allConnections
        ];
    }

    public static function dump(): void
    {
        Log::info('[UnityConnectionRegistry::dump] Dumping all active user connections:');

        foreach (self::$connectionsByUser as $userId => $connection) {
            Log::info("  User: {$userId} → Connection ID: " . $connection->id());
        }

        $globalConnections = Redis::hgetall(self::REDIS_KEY);
        Log::info('[UnityConnectionRegistry::dump] Global Redis connections: ' . json_encode($globalConnections));

        Log::info('[UnityConnectionRegistry::dump] End of dump.');
    }

    /**
     * Escuta mensagens publicadas no Redis e entrega para usuários locais.
     */
    public static function subscribeToRedisMessages(): void
    {
        Redis::subscribe([self::REDIS_CHANNEL], function ($message) {
            $data = json_decode($message, true);

            if (!isset($data['user_id'], $data['payload'])) {
                Log::warning("Invalid message received on Redis channel.");
                return;
            }

            $userId = (int)$data['user_id'];

            if (isset(self::$connectionsByUser[$userId])) {
                Log::info("Delivering cross-server message to local user $userId.");
                self::$connectionsByUser[$userId]->send(json_encode($data['payload']));
            }
        });
    }
}
