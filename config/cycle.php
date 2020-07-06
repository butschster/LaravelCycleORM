<?php

use Cycle\ORM\Schema;

return [
    'directories' => [
        app_path(),
    ],

    'database' => [
        'default' => 'default',

        'databases' => [
            'default' => [
                'connection' => env('DB_CONNECTION', 'postgres'),
            ],
        ],

        'connections' => [
            'sqlite' => [
                'driver' => Spiral\Database\Driver\SQLite\SQLiteDriver::class,
                'options' => [
                    'connection' => sprintf(
                        'sqlite:%s',
                        env('DB_DATABASE', database_path('database.sqlite'))
                    ),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD'),
                ]
            ],

            'mysql' => [
                'driver' => Spiral\Database\Driver\MySQL\MySQLDriver::class,
                'options' => [
                    'connection' => sprintf(
                        'mysql:host=%s;port=%d;dbname=%s',
                        env('DB_HOST', '127.0.0.1'),
                        env('DB_PORT', 3304),
                        env('DB_DATABASE', 'homestead')
                    ),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD'),
                ]
            ],

            'postgres' => [
                'driver' => Spiral\Database\Driver\Postgres\PostgresDriver::class,
                'options' => [
                    'connection' => sprintf(
                        'pgsql:host=%s;port=%d;dbname=%s;',
                        env('DB_HOST', '127.0.0.1'),
                        env('DB_PORT', 5432),
                        env('DB_DATABASE', 'homestead')
                    ),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD'),
                ],
            ],
        ],
    ],

    'schema' => [
        // Sync db schema with database without migrations
        'sync' => env('DB_SCHEMA_SYNC', false),

        // Cache schema
        // Кеширование схемы. После изменение сущности необходимо будет сбрасывать схему
        'cache' => [
            'storage' => env('DB_SCHEMA_CACHE_DRIVER', 'file'),
            'enabled' => (bool)env('DB_SCHEMA_CACHE', true),
        ],

        'defaults' => [
            Schema::MAPPER => Butschster\Cycle\Mapper::class,
            Schema::REPOSITORY => Butschster\Cycle\Repository::class,
            Schema::SOURCE => Cycle\ORM\Select\Source::class,
        ],
    ],

    'migrations' => [
        'directory' => database_path('migrations/cycle/'),
        'table' => env('DB_MIGRATIONS_TABLE', 'migrations'),
    ],

    // https://cycle-orm.dev/docs/advanced-promise#proxies-and-promises
    'relations' => [
        'materializer' => [
            'driver' => env('DB_MATERIALIZER_DRIVER', 'eval'),
            'drivers' => [
                'file' => [
                    'path' => storage_path('framework/cache/entities'),
                ]
            ]
        ],
    ],
];
