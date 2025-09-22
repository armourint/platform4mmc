<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // Explicit registration is optional if you keep load(__DIR__.'/Commands')
    protected $commands = [
        \App\Console\Commands\ImportEnvironmentalKpiFromExcel::class,
        \App\Console\Commands\ImportViabilityRulesFromExcel::class,
        \App\Console\Commands\ImportEnvBenchmarks::class,
        \App\Console\Commands\ImportProductsFromXlsx::class,
        \App\Console\Commands\SeedKnowledgeHubFromArchive::class,
        \App\Console\Commands\ImportEnvironmentalLayersFromExcel::class,
        \App\Console\Commands\ImportEnvironmentalA4FromExcel::class,
        \App\Console\Commands\ImportIrelandCounties::class,



        \App\Console\Commands\ImportEnvWallsFromExcel::class,
        \App\Console\Commands\ImportEnvCladdingFromExcel::class,
        \App\Console\Commands\ImportEnvSlabsFromExcel::class,
        \App\Console\Commands\ImportEnvA4FromExcel::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        //
    }

    protected function commands(): void
    {
        // Auto-discover any commands in app/Console/Commands
        $this->load(__DIR__.'/Commands');
    }
}
