<?php

namespace Butschster\Cycle\Entity\Relations;

use Cycle\ORM\Promise\MaterializerInterface;
use Cycle\ORM\Promise\Materizalizer\EvalMaterializer;
use Cycle\ORM\Promise\Materizalizer\FileMaterializer;
use Cycle\ORM\Promise\Materizalizer\ModificationInspector;
use Illuminate\Support\Manager;

class MaterializerManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('cycle.relations.materializer.driver') ?? 'eval';
    }

    protected function createEvalDriver(): MaterializerInterface
    {
        return new EvalMaterializer();
    }

    protected function createFileDriver(): MaterializerInterface
    {
        return new FileMaterializer(
            new ModificationInspector(),
            $this->config->get('cycle.relations.materializer.drivers.file.path')
        );
    }
}
