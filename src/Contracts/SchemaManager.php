<?php

namespace Butschster\Cycle\Contracts;

use Cycle\ORM\Schema;

interface SchemaManager
{
    public function createSchema(): Schema;

    public function flushSchemaData(): void;

    public function isSyncMode(): bool;
}
