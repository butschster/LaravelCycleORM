<?php

namespace Butschster\Cycle;

use Cycle\Annotated;
use Cycle\Migrations\GenerateMigrations;
use Cycle\ORM\Schema as ORMSchema;
use Cycle\Schema;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Spiral\Database\DatabaseManager;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Migrations\Migrator;
use Spiral\Tokenizer\ClassLocator;

class SchemaManager implements Contracts\SchemaManager
{
    private const SCHEMA_STORAGE_KEY = 'cycle.schema';

    protected Application $app;

    private ClassLocator $classLocator;

    private DatabaseManager $databaseManager;

    private Factory $cache;

    private Migrator $migrator;

    private MigrationConfig $config;

    public function __construct(
        Application $app,
        ClassLocator $classLocator,
        DatabaseManager $databaseManager,
        Factory $cache,
        Migrator $migrator,
        MigrationConfig $config
    )
    {
        $this->classLocator = $classLocator;
        $this->databaseManager = $databaseManager;
        $this->cache = $cache;
        $this->migrator = $migrator;
        $this->config = $config;
        $this->app = $app;
    }

    public function isSyncMode(): bool
    {
        return (bool)config('cycle.schema.sync');
    }

    public function createSchema(): ORMSchema
    {
        return new ORMSchema(
            $this->getCachedSchema()
        );
    }

    public function flushSchemaData(): void
    {
        $this->getCacheStorage()->forget(self::SCHEMA_STORAGE_KEY);
    }

    protected function generateSchemaData(): array
    {
        return (new Schema\Compiler())->compile(
            new Schema\Registry($this->databaseManager),
            $this->getSchemaGenerators(),
            (array)config('cycle.schema.defaults')
        );
    }

    protected function getSchemaGenerators(): array
    {
        $generators = [
            new Schema\Generator\ResetTables(),
            new Annotated\Embeddings($this->classLocator),
            new Annotated\Entities($this->classLocator),
            new Annotated\MergeColumns(),
            new Schema\Generator\GenerateRelations(),
            new Schema\Generator\ValidateEntities(),
            new Schema\Generator\RenderTables(),
            new Schema\Generator\RenderRelations(),
            new Annotated\MergeIndexes(),
            new Schema\Generator\GenerateTypecast(),
        ];

        // Если запускаем тесты, то синхронизируем схему на лету
        if ($this->isSyncMode()) {
            $generators[] = new Schema\Generator\SyncTables();
        } else { // Для остальных случаев создаем миграции
            $generators[] = new GenerateMigrations(
                $this->migrator->getRepository(),
                $this->config
            );
        }

        return $generators;
    }

    /**
     * @return CacheRepository
     */
    protected function getCacheStorage(): CacheRepository
    {
        return $this->cache->store(
            config('cycle.schema.cache.storage')
        );
    }

    protected function getCachedSchema(): array
    {
        if (!config('cycle.schema.cache.enabled')) {
            return $this->generateSchemaData();
        }

        return $this->getCacheStorage()->rememberForever(
            self::SCHEMA_STORAGE_KEY,
            fn() => $this->generateSchemaData()
        );
    }
}
