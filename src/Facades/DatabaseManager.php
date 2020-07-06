<?php

namespace Butschster\Cycle\Facades;

use Illuminate\Support\Facades\Facade;
use Spiral\Database\DatabaseProviderInterface;

class DatabaseManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return DatabaseProviderInterface::class;
    }
}
