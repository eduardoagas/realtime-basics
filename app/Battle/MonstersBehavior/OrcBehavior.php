<?php

namespace App\Battle\MonstersBehavior;

class OrcBehavior implements MonsterBehaviorInterface
{
    public function decideAction(array $monsterData, array $battleState): ?string
    {

        $stamina = $monsterData['current_stamina'] ?? 0;

        if ($stamina < 20) {
            return null;
        }
        // 70% chance de atacar
        if (rand(1, 100) <= 70) {
            return 'attack';
        }
        return 'wait';
    }
}
