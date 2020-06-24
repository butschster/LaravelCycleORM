<?php

namespace Butschster\Cycle\Commands;

use Illuminate\Console\Command;
use Butschster\Cycle\Contracts\SchemaManager;

class RefreshSchema extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'cycle:refresh';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Refresh database schema';

    public function handle(SchemaManager $schemaManager): void
    {
        $this->call('db:wipe');

        $schemaManager->flushSchemaData();

        $this->info('Database schema cache flushed.');

        if ($schemaManager->isSyncMode()) {
            $schemaManager->createSchema();
        } else {
            $this->call('cycle:migrate');
            $schemaManager->createSchema();
            $this->call('cycle:migrate');
        }

        $this->info('Database schema updated.');
    }
}
