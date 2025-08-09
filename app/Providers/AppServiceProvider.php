<?php

namespace App\Providers;

use App\Battle\BattleActions;
use App\Battle\BattleManager;
use Laravel\Octane\Facades\Octane;
use Illuminate\Support\Facades\Log;
use App\Services\Battle\StaminaService;
use Illuminate\Support\ServiceProvider;
use App\Services\UnityConnectionRegistry;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use App\Services\Battle\BattleBroadcaster;
use App\Services\MyPubSubIncomingMessageHandler;
use App\Listeners\WebSockets\Redis\UnityPubSubListener;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubProvider;
use Laravel\Reverb\Servers\Reverb\Publishing\RedisClientFactory;
use Laravel\Reverb\Servers\Reverb\Publishing\RedisPubSubProvider;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubIncomingMessageHandler;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Log::info("AppServiceProvider register");

        // Substitui totalmente a implementação padrão do Reverb
        $this->app->singleton(
            \Laravel\Reverb\Servers\Reverb\Contracts\PubSubIncomingMessageHandler::class,
            function ($app) {
                return new \App\Services\MyPubSubIncomingMessageHandler(
                    $app->make(\App\Services\UnityConnectionRegistry::class)
                );
            }
        );

        // Garante que mesmo se pedirem explicitamente pelo Pusher handler, use o nosso
        $this->app->bind(
            \Laravel\Reverb\Protocols\Pusher\PusherPubSubIncomingMessageHandler::class,
            \App\Services\MyPubSubIncomingMessageHandler::class
        );


        // Registra o provider RedisPubSubProvider usando o handler acima e canal do env
        $this->app->singleton(PubSubProvider::class, function ($app) {
            $redisClientFactory = $app->make(RedisClientFactory::class);
            $messageHandler = $app->make(PubSubIncomingMessageHandler::class);
            $channel = env('REVERB_REDIS_CHANNEL', 'reverb-channel');

            return new RedisPubSubProvider($redisClientFactory, $messageHandler, $channel);
        });

        // Singleton do UnityConnectionRegistry
        $this->app->singleton(UnityConnectionRegistry::class, function ($app) {
            return new UnityConnectionRegistry($app->make(PubSubProvider::class));
        });

        // Outros singletons do app
        $this->app->singleton(BattleBroadcaster::class);
        $this->app->singleton(BattleActions::class);
        $this->app->singleton(StaminaService::class);
        $this->app->singleton(BattleManager::class);
    }

    public function boot(): void
    {
        Log::info("AppServiceProvider boot");

        if (!class_exists(Octane::class) || !app()->bound('octane')) {
            Log::info("Octane não está rodando, pulando listeners do worker.");
            return;
        }

        $dispatcher = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);

        // Inscreve o listener uma vez por worker quando o worker inicia
        $dispatcher->listen(WorkerStarting::class, function () {
            Log::info("WorkerStarting event fired");

            $pubSub = app(PubSubProvider::class);
            $registry = app(UnityConnectionRegistry::class);

            // Cria o listener que escuta o canal Redis e encaminha mensagens
            new UnityPubSubListener($pubSub, $registry);

            Log::info('UnityPubSubListener iniciado no worker do Octane.');
        });

        $dispatcher->listen(WorkerStopping::class, function () {
            Log::info("WorkerStopping event fired");
            // Você pode colocar aqui lógica de limpeza se quiser
        });

        Log::info("AppServiceProvider boot finalizado");

        if (app()->bound('octane')) {
            Octane::tick('battle-ticker', function () {
                try {
                    $battleManager = app(BattleManager::class);
                    Log::info("Executando tick do battle-ticker");
                    $battleManager->processBattles();
                } catch (\Throwable $e) {
                    Log::error("Erro no tick battle-ticker: " . $e->getMessage());
                }
            }, 5);
        }
    }
}
