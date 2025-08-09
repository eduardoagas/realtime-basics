<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubIncomingMessageHandler;

class MyPubSubIncomingMessageHandler implements PubSubIncomingMessageHandler
{
    protected UnityConnectionRegistry $registry;

    public function __construct(UnityConnectionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * O método agora recebe só o payload (string JSON).
     */
    public function handle(string $payload): void
    {
        Log::info("jesus chr");
        $data = json_decode($payload, true);

        if (!$data || !isset($data['user_id'], $data['payload'])) {
            return;
        }

        $userId = $data['user_id'];
        $payload = $data['payload'];

        $connection = $this->registry->get($userId);

        if ($connection) {
            $connection->send(json_encode($payload));
        } else {
            Log::info("PubSub message for user $userId but no local connection found.");
        }
    }
}
