<?php

namespace App\Services\Battle;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class StaminaService
{
    public function initializeStamina(int $now, float $maxStamina, float $agility): array
    {

        return [
            'start_time' => $now,
            'initial_stamina' => 0,
            'max_stamina' => $maxStamina,
            'agility' => $agility,
        ];
    }

    public static function getCurrentStamina(string $battleId, string $id, string $type = 'character'): float
    {
        $field = "{$type}:{$id}";
        $key = "battle:$battleId:stamina_data";
        $data = Redis::hget($key, $field);

        Log::info("📥 [getCurrentStamina] Buscando stamina", [
            'redis_key' => $key,
            'field' => $field,
            'raw_data' => $data,
        ]);

        if (!$data) {
            Log::warning("⚠️ Nenhum dado de stamina encontrado", [
                'battleId' => $battleId,
                'field' => $field
            ]);
            return 0;
        }

        $parsed = json_decode($data, true);

        $startTime = (int) ($parsed['start_time'] ?? 0);
        $initial = (float) ($parsed['initial_stamina'] ?? 0);
        $sMax = (float) ($parsed['max_stamina'] ?? 0);
        $agi = (float) ($parsed['agility'] ?? 0);
        $agi = max(1.0, $agi);
        $elapsed = now()->timestamp - $startTime;

        Log::info("📊 [getCurrentStamina] Dados extraídos:", compact('startTime', 'initial', 'sMax', 'agi', 'elapsed'));

        // Constantes
        $minRate = 1;           // menor taxa (agi = 1)
        $maxRate = 14;          // maior taxa (agi = 300)
        $maxAgi = 300;          // agilidade máxima
        $alpha = 0.6;           // suavidade da curva de agilidade
        $beta = 0.4;            // influência do quanto a barra está cheia
        $B = 1.7;               // máximo multiplicador do betaFactor (aumento máximo da taxa)

        // Cálculo da taxa base de regeneração (sem fator beta)
        // Calcula fator baseado na agilidade (curva suavizada pelo alpha)
        $agiFactor = pow(min($agi / $maxAgi, 1), $alpha);
        // Calcula taxa base de regeneração (stamina/segundo)
        $baseRegen = $minRate + ($maxRate - $minRate) * $agiFactor;

        // Fator beta: quanto mais stamina bruta, mais rápido regenera
        $maxInitial = 100; // Define o teto de influência do beta (ex: 100 pontos)
        $clampedInitial = min($initial, $maxInitial);
        $betaFactor = 1 + ($B - 1) * pow($clampedInitial / $maxInitial, $beta);

        // Recuperação
        // Quantidade de stamina regenerada desde o último cálculo
        $recovered = $baseRegen * $betaFactor * $elapsed;
        // Estamina atualizada limitada ao máximo permitido
        $stamina = min($initial + $recovered, $sMax);


        Log::info("✅ [getCurrentStamina] Resultado calculado:", [
            'stamina_calculada' => $stamina,
            'stamina_limitada' => min($stamina, $sMax)
        ]);

        return min($stamina, $sMax);
    }

    public static function consumeStamina(string $battleId, string $id, float $amount, string $type = 'character'): bool
    {
        $field = "{$type}:{$id}";
        $key = "battle:$battleId:stamina_data";
        $data = Redis::hget($key, $field);

        Log::info("🛠️ [consumeStamina] Tentando consumir stamina", [
            'field' => $field,
            'amount' => $amount,
            'raw_data' => $data,
        ]);

        if (!$data) return false;

        $parsed = json_decode($data, true);
        $currentStamina = self::getCurrentStamina($battleId, $id, $type);

        if ($currentStamina < $amount) {
            Log::warning("❌ [consumeStamina] Stamina insuficiente", [
                'disponível' => $currentStamina,
                'necessária' => $amount
            ]);
            return false;
        }

        // Gasto aprovado: resetar start_time e stamina atual
        $parsed['initial_stamina'] = $currentStamina - $amount;
        $parsed['start_time'] = now()->timestamp;

        Redis::hset($key, $field, json_encode($parsed));

        Log::info("✅ [consumeStamina] Stamina atualizada com sucesso", [
            'nova_stamina' => $parsed['initial_stamina']
        ]);

        return true;
    }
}
