<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportViabilityRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dst:import-viability {json} {--version=v1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Viability Rules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
