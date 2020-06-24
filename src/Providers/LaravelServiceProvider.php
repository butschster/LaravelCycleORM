<?php

namespace Butschster\Cycle\Providers;

use Butschster\Cycle\Commands;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\MaterializerInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction;
use Cycle\ORM\TransactionInterface;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Butschster\Cycle\Contracts\SchemaManager as SchemaManagerContract;
use Butschster\Cycle\SchemaManager;
use Butschster\Cycle\Auth\UserProvider;
use Butschster\Cycle\Database\Connection as DatabaseConnection;
use Butschster\Cycle\Entity\Relations\MaterializerManager;
use Butschster\Cycle\Migrations\FileRepository;
use Spiral\Core\FactoryInterface as SpiralContainerInterface;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Migrations\Migrator;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;

class LaravelServiceProvider extends BaseServiceProvider
{
    /**
     * The array of resolved Faker instances.
     *
     * @var array
     */
    protected static $fakers = [];

    public function boot()
    {
        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__ . '/../../config/cycle.php', 'cycle');
        }

        $this->publishes([
            __DIR__ . '/../../config/cycle.php' => config_path('cycle.php'),
        ], 'config');

        $this->registerAuthUserProvider();
        $this->registerDatabaseConnection();
    }

    public function register()
    {
        AnnotationRegistry::registerLoader('class_exists');
        $this->registerConsoleCommands();
        $this->registerDatabaseConfig();
        $this->registerDatabaseManager();
        $this->registerDatabaseSchema();
        $this->registerRelationMaterializer();
        $this->registerOrm();
        $this->registerTokenizer();
        $this->registerClassLocator();
        $this->registerMigrationConfig();
        $this->registerMigrator();
        $this->registerDatabaseFactory();
        $this->registerSchemaManager();
    }

    protected function registerConsoleCommands(): void
    {
        $this->commands([
            Commands\Migrate::class,
            Commands\RefreshSchema::class,
        ]);
    }

    public function registerDatabaseConnection(): void
    {
        Connection::resolverFor('cycle', function () {
            return new DatabaseConnection(
                $this->app[ORMInterface::class],
                config('database.connections.postgres')
            );
        });
    }

    public function registerAuthUserProvider(): void
    {
        $this->app->make('auth')->provider('cycle', function ($app, $config) {
            return new UserProvider(
                $app[ORMInterface::class],
                $config['model'],
                $app['hash'],
            );
        });
    }

    protected function registerDatabaseConfig(): void
    {
        $this->app->singleton(DatabaseConfig::class, function () {
            return new DatabaseConfig(
                config('cycle.database')
            );
        });
    }

    protected function registerDatabaseManager(): void
    {
        $this->app->singleton(DatabaseManager::class, function () {
            return new DatabaseManager(
                $this->app[DatabaseConfig::class]
            );
        });
    }

    protected function registerDatabaseSchema(): void
    {
        $this->app->singleton(SchemaInterface::class, function () {
            return $this->app[SchemaManagerContract::class]->createSchema();
        });
    }

    protected function registerRelationMaterializer(): void
    {
        $this->app->singleton(MaterializerInterface::class, function () {
            return (new MaterializerManager($this->app))->driver();
        });
    }

    protected function registerOrm(): void
    {
        $this->app->singleton(TransactionInterface::class, function () {
            return new Transaction(
                $this->app[ORMInterface::class]
            );
        });

        $this->app->singleton(SpiralContainerInterface::class, function () {
            $container = new \Spiral\Core\Container();
            $container->bindSingleton(TransactionInterface::class, fn() => $this->app[TransactionInterface::class]);

            return $container;
        });

        $this->app->singleton(FactoryInterface::class, function () {
            return new Factory(
                $this->app[DatabaseManager::class],
                null,
                $this->app[SpiralContainerInterface::class]
            );
        });

        $this->app->singleton(ORMInterface::class, function () {
            return new \Cycle\ORM\ORM(
                $this->app[FactoryInterface::class],
                $this->app[SchemaInterface::class]
            );
        });
    }

    protected function registerTokenizer(): void
    {
        $this->app->singleton(Tokenizer::class, function () {
            return new Tokenizer(
                new TokenizerConfig(config('cycle'))
            );
        });
    }

    protected function registerClassLocator(): void
    {
        $this->app->singleton(ClassLocator::class, function () {
            return $this->app[Tokenizer::class]
                ->classLocator(config('cycle.directories'));
        });
    }

    protected function registerMigrationConfig(): void
    {
        $this->app->singleton(MigrationConfig::class, function () {
            return new MigrationConfig(
                (array)config('cycle.migrations')
            );
        });
    }

    protected function registerMigrator(): void
    {
        $this->app->singleton(Migrator::class, function () {
            $config = $this->app[MigrationConfig::class];
            $databaseManager = $this->app[DatabaseManager::class];

            $repository = new FileRepository($config);

            return new Migrator(
                ...with($config, fn(MigrationConfig $config) => [
                    $config,
                    $databaseManager,
                    $repository,
                ])
            );
        });
    }

    protected function registerDatabaseFactory(): void
    {
        $this->app->singleton(FakerGenerator::class, function ($app, $parameters) {
            $locale = $parameters['locale'] ?? $app['config']->get('app.faker_locale', 'en_US');

            if (!isset(static::$fakers[$locale])) {
                static::$fakers[$locale] = FakerFactory::create($locale);
            }

            static::$fakers[$locale]->unique(true);

            return static::$fakers[$locale];
        });

        $this->app->singleton(\Butschster\Cycle\Database\Factory::class, function ($app) {
            $factory = new\Butschster\Cycle\Database\Factory(
                $app[ORMInterface::class],
                $app->make(FakerGenerator::class)
            );

            return $factory->load(
                $this->app->databasePath('factories')
            );
        });
    }

    protected function registerSchemaManager(): void
    {
        $this->app->singleton(SchemaManagerContract::class, function () {
            return new SchemaManager(
                $this->app,
                $this->app[ClassLocator::class],
                $this->app[DatabaseManager::class],
                $this->app[\Illuminate\Contracts\Cache\Factory::class],
                $this->app[Migrator::class],
                $this->app[MigrationConfig::class]
            );
        });
    }
}

