<?php

namespace App\WebSockets\Handlers;

use App\Services\Battle\SkillService;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\Connection;
use App\Services\UnityConnectionRegistry;
use App\Exceptions\SkillCooldownException;
use App\WebSockets\Contracts\HandlesUnityEvent;
use App\Exceptions\InsufficientStaminaException;

class UseSkillHandler implements HandlesUnityEvent
{
    private SkillService $skillService;

    public function __construct()
    {
        $this->skillService = new SkillService();
    }

    public function handle(array $payload, int $userId, string $token, Connection $connection): void
    {
        $data = $payload['data'] ?? [];
        $skillId = $data['skill_id'] ?? null;
        $targetType = $data['target_type'] ?? null;
        $targetId = $data['target_id'] ?? null;

        $session = Redis::hgetall("session:$token");
        $battleId = $session['battle_instance_id'] ?? null;
        $characterId = $session['character_id'] ?? null;

        if (!$battleId || !$characterId || !$skillId) {
            $connection->send(json_encode(['error' => 'Dados insuficientes para usar skill']));
            return;
        }

        $characterData = Redis::hgetall("character_session:$characterId");
        $enemyData = null;

        if ($targetType === 'enemy' && $targetId) {
            $enemyJson = Redis::hget("battle:$battleId:monsters", $targetId);
            if (!$enemyJson) {
                $connection->send(json_encode(['error' => 'Inimigo não encontrado']));
                return;
            }
            $enemyData = json_decode($enemyJson, true);
            $enemyData['id'] = $targetId; // para manter o id
        }

        try {
            $result = $this->skillService->applySkill($characterData, $enemyData, $battleId, (int)$skillId);

            $userIds = Redis::smembers("battle:$battleId:users");
            UnityConnectionRegistry::broadcastToUsers($userIds, $result);
        } catch (InsufficientStaminaException | SkillCooldownException $e) {
            $connection->send(json_encode(['error' => $e->getMessage()]));
        } catch (\Exception $e) {
            $connection->send(json_encode(['error' => 'Erro inesperado ao usar skill']));
        }
    }
}
