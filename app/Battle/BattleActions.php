<?php

namespace App\Battle;

use Illuminate\Support\Facades\Log;
use App\Services\Battle\StaminaService;

class BattleActions
{
    /**
     * Executa a ação do monstro contra o personagem alvo.
     *
     * @param array &$monster
     * @param string $action
     * @param array &$targetCharacter
     * @param string $battleId
     * @return void
     */
    public static function executeAction(array &$monster, string $action, array &$targetCharacter, string $battleId): void
    {
        switch ($action) {
            case 'attack':
                self::attack($monster, $targetCharacter, $battleId);
                break;

            case 'wait':
                self::wait($monster);
                break;

            // Exemplo para habilidades futuras
            case 'special_skill':
                self::specialSkill($monster, $targetCharacter, $battleId);
                break;

            default:
                Log::warning("Monster {$monster['name']} performed unknown action '$action'.");
                break;
        }
    }

    protected static function attack(array &$monster, array &$targetCharacter, string $battleId): void
    {
        $damage = max(0, $monster['pattack'] - ($targetCharacter['defense'] ?? 0));
        $targetCharacter['hp'] = max(0, ($targetCharacter['hp'] ?? 0) - $damage);
        $monster['stamina'] = max(0, ($monster['stamina'] ?? 0) - 10);

        Log::info("Monster {$monster['name']} attacked {$targetCharacter['name']} for $damage damage. Target HP now {$targetCharacter['hp']}.");

        if ($targetCharacter['hp'] <= 0) {
            Log::info("Character {$targetCharacter['name']} died!");
        }

        StaminaService::consumeStamina($battleId, (string)$monster['id'], 10, 'monster');
    }

    protected static function wait(array &$monster): void
    {
        Log::info("Monster {$monster['name']} waits.");
    }

    protected static function specialSkill(array &$monster, array &$targetCharacter, string $battleId): void
    {
        // Exemplo para implementar uma habilidade especial
        Log::info("Monster {$monster['name']} uses special skill on {$targetCharacter['name']}.");
        // Implemente a lógica da skill aqui
    }
}
