<?php

namespace Butschster\Cycle\Facades;

use Cycle\ORM\ORMInterface;
use Illuminate\Support\Facades\Facade;

class ORM extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ORMInterface::class;
    }
}
