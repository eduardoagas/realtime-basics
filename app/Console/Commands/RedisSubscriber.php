<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Services\UnityConnectionRegistry;

class RedisSubscriber extends Command
{
    protected $signature = 'redis:subscriber';
    protected $description = 'Listen to Redis channel to send messages to local websocket connections';

    public function handle()
    {
        $this->info('Listening to Redis channel unity:messages...');

        Redis::subscribe(['unity:messages'], function ($message) {
            $data = json_decode($message, true);
            $userId = $data['user_id'] ?? null;
            $payload = $data['payload'] ?? null;

            if ($userId && $payload) {
                $connection = UnityConnectionRegistry::get((string)$userId);
                if ($connection) {
                    $connection->send(json_encode($payload));
                    $this->info("Sent message to user $userId");
                } else {
                    $this->warn("User $userId connection not found locally");
                }
            }
        });
    }
}
