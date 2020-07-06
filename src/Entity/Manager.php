<?php

namespace Butschster\Cycle\Entity;

use Butschster\Cycle\Contracts\EntityManager;
use Cycle\ORM\ORMInterface;
use Illuminate\Support\Collection;

class Manager implements EntityManager
{
    private ORMInterface $orm;

    public function __construct(ORMInterface $orm)
    {
        $this->orm = $orm;
    }

    public function findByPK(string $entity, $id)
    {
        return $this->orm->getRepository($entity)
            ->findByPK($id);
    }

    public function findOne(string $entity, array $scope = [])
    {
        return $this->orm->getRepository($entity)
            ->findOne($scope);
    }

    public function findAll(string $entity, array $scope = [], array $orderBy = []): Collection
    {
        return $this->orm->getRepository($entity)
            ->findAll($scope, $orderBy);
    }

    public function persist(object $entity, bool $cascade = true, bool $run = true): void
    {
        $this->orm->getRepository($entity)
            ->persist($entity, $cascade, $run);
    }

    public function delete(object $entity, bool $cascade = true, bool $run = true): void
    {
        $this->orm->getRepository($entity)
            ->delete($entity, $cascade, $run);
    }
}
