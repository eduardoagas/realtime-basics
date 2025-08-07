<?php

namespace App\Providers;

use Laravel\Octane\Facades\Octane;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /* Log::info('AppServiceProvider boot chamado!');

        Octane::tick('my-ticker2', function () {
            echo "Tick executado em " . now() . PHP_EOL;
            Log::info('Tick executado no AppServiceProvider boot.');
        }, 5);*/
    }
}
