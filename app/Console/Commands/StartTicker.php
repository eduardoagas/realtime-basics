<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Octane\Facades\Octane;
use Illuminate\Support\Facades\Log;

class StartTicker extends Command
{
    protected $signature = 'ticker:start';
    protected $description = 'Start a recurring task using Octane Ticker';

    public function handle()
    {
        $this->info('Starting ticker...');

        Octane::tick('my-ticker', function () {
            $time = now()->toDateTimeString();
            echo "Tick executado em " . now() . PHP_EOL;
            Log::info("🕒 Ticker running at $time");
            $this->info("Ticker ticked at $time");
        }, 5); // Executa a cada 5 segundos

        return 0;
    }
}
