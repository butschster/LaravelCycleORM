<?php

namespace Butschster\Cycle\Commands;

use Illuminate\Console\Command;
use Spiral\Migrations\Migrator;

class Migrate extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'cycle:migrate';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Run cycle orm migrations from directory';

    public function handle(Migrator $migrator)
    {
        if (!$migrator->isConfigured()) {
            $migrator->configure();
        }

        while (($migration = $migrator->run()) !== null) {
            $this->info('Migrate ' . $migration->getState()->getName());
        }
    }
}
