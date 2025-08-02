<?php

namespace App\WebSocket\Handlers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;
use App\WebSocket\Contracts\HandlesUnityEvent;

class UseSkillHandler implements HandlesUnityEvent
{
    private array $skills = [
        1 => ['name' => 'Fire Ball', 'type' => 'damage', 'power' => 25],
        2 => ['name' => 'Raise Defense', 'type' => 'buff', 'stat' => 'defense', 'bonus' => 5, 'duration' => 3],
    ];

    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {

        Log::info("UseSkillPayload = " . json_encode($payload));

        // Extrai data como array ou null
        $data = $payload['data'] ?? null;

        $skillId    = $data['skill_id']    ?? null;
        $targetType = $data['target_type'] ?? null;
        $targetId   = $data['target_id']   ?? null;

        if (
            ! is_array($data)             // garante que data exista e seja array
            || ! $skillId                 // skill_id ausente ou zero
            || ! isset($this->skills[$skillId])  // skill não existe
            || ! $targetType              // target_type ausente
        ) {
            $connection->send(json_encode([
                'error' => 'Invalid skill usage request'
            ]));
            return;
        }

        $session = Redis::hgetall("session:$token");
        Log::info("session = " . json_encode($session));
        $battleId = $session['battle_instance_id'] ?? null;
        Log::info("battleId = " . $battleId);

        if (!$battleId || !Redis::exists("battle:$battleId:characters")) {
            //TODO: Mudei acima para character... conferir se battleid está nula ? manter character msm?
            $connection->send(json_encode(['error' => 'Not in a valid battle instance']));
            return;
        }

        Log::info("im here btich");
        $characterId = $session['character_id'] ?? null;

        if (!$characterId) {
            $connection->send(json_encode([
                'event' => 'unity-response',
                'data' => ['error' => 'Character not found in session']
            ]));
            return;
        }
        $characterData = Redis::hgetall("character_session:$characterId");

        if (!$characterData) {
            $connection->send(json_encode(['error' => 'Character session not found']));
            return;
        }

        $skill = $this->skills[$skillId];

        if ($skill['type'] === 'damage') {
            if ($targetType !== 'enemy' || !$targetId || !Redis::hexists("battle:$battleId:monsters", $targetId)) {
                $connection->send(json_encode(['error' => 'Invalid monster target']));
                return;
            }

            $monster = json_decode(Redis::hget("battle:$battleId:monsters", $targetId), true);
            $damage = max(0, $skill['power'] + $characterData['mattack'] - $monster['defense']);
            $monster['hp'] = max(0, $monster['hp'] - $damage);

            Redis::hset("battle:$battleId:monsters", $targetId, json_encode($monster));

            $eventPayload = [
                'event' => 'skill_used',
                'user_id' => $userId,
                'skill_name' => $skill['name'],
                'target' => $targetId,
                'result' => [
                    'damage' => $damage,
                    'monster_hp' => $monster['hp'],
                ],
            ];
        }

        if ($skill['type'] === 'buff') {
            if ($targetType !== 'self') {
                $connection->send(json_encode(['error' => 'This skill can only target self']));
                return;
            }

            $stat = $skill['stat'];
            $bonus = $skill['bonus'];

            Redis::hset("battle:$battleId:buffs", $characterData['id'], json_encode([
                'stat' => $stat,
                'bonus' => $bonus,
                'turns_left' => $skill['duration'],
            ]));

            $eventPayload = [
                'event' => 'skill_used',
                'user_id' => $userId,
                'skill_name' => $skill['name'],
                'target' => 'self',
                'result' => [
                    'stat_buffed' => $stat,
                    'bonus' => $bonus,
                ],
            ];
        }

        $userIds = Redis::smembers("battle:$battleId:users");
        UnityConnectionRegistry::broadcastToUsers($userIds, $eventPayload); //isso n é client-necessário exatamente, apenas talvez um "notice" que atualize algum broadcast update stats de batalha total
    }
}
