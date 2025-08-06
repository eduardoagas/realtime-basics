<?php

namespace App\Services\Battle;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Services\Battle\StaminaService;
use App\Exceptions\SkillCooldownException;
use App\Exceptions\InsufficientStaminaException;

class SkillService
{
    private StaminaService $staminaService;

    private array $skills = [
        1 => [
            'id' => 1,
            'name' => 'Fire Ball',
            'type' => 'damage',
            'power' => 25,
            'stamina_cost' => 5,
            'pre_delay' => 500,  // milissegundos
            'post_delay' => 1000,
        ],
        2 => [
            'id' => 2,
            'name' => 'Raise Defense',
            'type' => 'buff',
            'stat' => 'defense',
            'bonus' => 5,
            'duration' => 3,
            'stamina_cost' => 10,
            'pre_delay' => 300,
            'post_delay' => 500,
        ],
    ];

    public function __construct()
    {
        $this->staminaService = new StaminaService();
    }

    /**
     * @param array $characterData Dados do personagem
     * @param array|null $enemyData Dados do inimigo (opcional, para skills que afetam inimigos)
     * @param string $battleId ID da batalha
     * @param int $skillId ID da skill
     * @return array Resultado da aplicação da skill
     * @throws SkillCooldownException
     * @throws InsufficientStaminaException
     */
    public function applySkill(array $characterData, ?array $enemyData, string $battleId, int $skillId): array
    {

        Log::info("⚔️ [applySkill] Início da aplicação da skill", [
            'battle_id' => $battleId,
            'character_id' => $characterData['id'] ?? null,
            'skill_id' => $skillId,
        ]);

        if (!isset($this->skills[$skillId])) {
            throw new \InvalidArgumentException("Skill $skillId not found");
        }

        $skill = $this->skills[$skillId];
        $charId = $characterData['id'];
        $now = now()->timestamp;

        //Verifica cooldown Global
        $redisKey = "global_cooldown_at:{$battleId}:{$charId}";
        $readyAt = Redis::get($redisKey);

        if ($readyAt && $now < (int)$readyAt) {
            throw new SkillCooldownException("Aguarde até " . date('H:i:s', (int)$readyAt) . " para usar outra skill.");
        }

        // Verifica cooldown da skill
        /*$redisKey = "skill_ready_at:{$battleId}:{$charId}:{$skillId}";
        $readyAt = Redis::get($redisKey);
        if ($readyAt && $now < (int)$readyAt) {
            throw new SkillCooldownException("Skill em cooldown até " . date('H:i:s', (int)$readyAt));
        }*/

        // Obtém stamina atual
        $currentStamina = $this->staminaService->getCurrentStamina($battleId, $charId, 'character');

        if ($currentStamina < $skill['stamina_cost']) {
            throw new InsufficientStaminaException("Stamina insuficiente ({$currentStamina} / {$skill['stamina_cost']})");
        }

        // Consome stamina
        $success = $this->staminaService->consumeStamina($battleId, $charId, $skill['stamina_cost']);
        if (!$success) {
            throw new InsufficientStaminaException("Falha ao consumir stamina");
        }

        $resultPayload = [];

        if ($skill['type'] === 'damage') {
            if (!$enemyData) {
                throw new \InvalidArgumentException("Enemy data is required for damage skills");
            }

            // Calcula dano simples (pode refinar)
            $damage = max(0, $skill['power'] + ($characterData['mattack'] ?? 0) - ($enemyData['defense'] ?? 0));
            $enemyData['hp'] = max(0, ($enemyData['hp'] ?? 0) - $damage);

            // Atualiza no Redis
            Redis::hset("battle:{$battleId}:monsters", $enemyData['id'], json_encode($enemyData));

            $resultPayload['monster_hp'] = $enemyData['hp'];
            $resultPayload['damage_dealt'] = $damage;
        } elseif ($skill['type'] === 'buff') {
            $buff = [
                'skill_id' => $skillId,
                'stat' => $skill['stat'],
                'bonus' => $skill['bonus'],
                'duration' => $skill['duration'],
            ];

            Redis::hset("battle:{$battleId}:buffs", $charId, json_encode($buff));

            $resultPayload['buff_applied'] = $buff;
        }

        // Define cooldown no Redis para post_delay (em segundos)
        $newReadyAt = $now + (int)($skill['post_delay'] / 1000);
        Redis::set($redisKey, $newReadyAt);

        return [
            'battle_id' => $battleId,
            'character_id' => $charId,
            'skill_id' => $skillId,
            'result' => $resultPayload,
            'current_stamina' => $this->staminaService->getCurrentStamina($battleId, $charId, 'character'),
            'pre_delay' => $skill['pre_delay'],
            'post_delay' => $skill['post_delay'],
        ];
    }
}
