<?php

namespace Butschster\Cycle\Facades;

use Butschster\Cycle\Contracts\EntityManager as EntityManagerContract;
use Illuminate\Support\Facades\Facade;

class EntityManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return EntityManagerContract::class;
    }
}

