<?php

namespace App\Services;

use React\EventLoop\LoopInterface;
use Clue\React\Redis\Factory as ReactRedisFactory;

class RedisClientFactory
{
    protected string $redisUrl;

    public function __construct()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);
        $this->redisUrl = "redis://{$host}:{$port}";
    }

    /**
     * Cria um cliente Redis para o ReactPHP.
     */
    public function createClient(LoopInterface $loop)
    {
        $factory = new ReactRedisFactory($loop);

        // Cria o cliente assincrono que será usado para publicar ou assinar
        return $factory->createClient($this->redisUrl);
    }
}
