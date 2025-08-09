<?php

namespace App\Battle\MonstersBehavior;

use Illuminate\Support\Facades\Log;

class GoblinBehavior implements MonsterBehaviorInterface
{
    public function decideAction(array $monsterData, array $battleState): ?string
    {
        $stamina = $monsterData['current_stamina'] ?? 0;

        Log::info("TO DECIDINDO com stamina atual $stamina");

        if ($stamina < 10) {
            return null; // pouca stamina, espera
        }
        if (rand(1, 100) <= 50) {
            return 'attack';
        }
        return 'wait';
    }
}
