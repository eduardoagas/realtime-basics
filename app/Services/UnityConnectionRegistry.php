<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Contracts\Connection;

class UnityConnectionRegistry
{
    protected static array $connectionsByUser = [];

    public static function register(string $userId, Connection $connection): void
    {
        Log::info("Registering user $userId with connection ID: " . $connection->id());
        self::$connectionsByUser[$userId] = $connection;
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
                break;
            }
        }
    }

    public static function all(): array
    {
        Log::info("Current connections: " . json_encode(array_keys(self::$connectionsByUser)));
        return self::$connectionsByUser;
    }

    public static function dump(): void
    {
        Log::info('[UnityConnectionRegistry::dump] Dumping all active user connections:');

        foreach (self::$connectionsByUser as $userId => $connection) {
            Log::info("  User: {$userId} → Connection ID: " . $connection->id());
        }

        Log::info('[UnityConnectionRegistry::dump] End of dump.');
    }
}
