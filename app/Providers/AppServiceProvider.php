<?php

namespace App\Providers;

use App\Listeners\HandleUnityClientEvent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Reverb\Events\MessageReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            MessageReceived::class,
            [HandleUnityClientEvent::class, 'handle']
        );
    }
}
