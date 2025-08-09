<?php

namespace App\Listeners\WebSockets\Redis;

use Illuminate\Support\Facades\Log;
use App\Services\UnityConnectionRegistry;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubProvider;

class UnityPubSubListener
{
    protected PubSubProvider $pubSub;
    protected UnityConnectionRegistry $registry;
    protected static bool $subscribed = false;

    public function __construct(PubSubProvider $pubSub, UnityConnectionRegistry $registry)
    {
        $this->pubSub = $pubSub;
        $this->registry = $registry;

        if (!self::$subscribed) {
            $this->startListening();
            self::$subscribed = true;
        }
    }

    public function startListening(): void
    {
        Log::info('UnityPubSubListener started listening on Redis channel');

        $channel = env('REVERB_REDIS_CHANNEL', 'reverb-channel');

        $this->pubSub->subscribe($channel, function ($channel, $message) {
            $data = json_decode($message, true);

            if (!isset($data['user_id'], $data['payload'])) {
                Log::warning("Mensagem inválida recebida no canal Redis: $message");
                return;
            }

            $userId = $data['user_id'];
            $payload = $data['payload'];

            $connection = $this->registry->get($userId);
            if ($connection) {
                $connection->send(json_encode($payload));
            } else {
                Log::info("Conexão local para usuário $userId não encontrada.");
            }
        });
    }
}
