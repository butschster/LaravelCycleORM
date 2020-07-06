<?php

namespace Butschster\Cycle\Contracts;

use Illuminate\Support\Collection;

interface EntityManager
{
    /**
     * Find entity by PK
     *
     * @param string $entity
     * @param string|int $id
     * @return object|null
     */
    public function findByPK(string $entity, $id);

    /**
     * Find entity using given scope (where).
     *
     * @param array $scope
     * @return null|object
     */
    public function findOne(string $entity, array $scope = []);

    /**
     * Find multiple entities using given scope and sort options.
     *
     * @param string $entity
     * @param array $scope
     * @param array $orderBy
     *
     * @return Collection
     */
    public function findAll(string $entity, array $scope = [], array $orderBy = []): Collection;

    /**
     * @param object $entity
     * @param bool $cascade
     * @param bool $run
     */
    public function persist(object $entity, bool $cascade = true, bool $run = true): void;

    /**
     * @param object $entity
     * @param bool $cascade
     * @param bool $run
     */
    public function delete(object $entity, bool $cascade = true, bool $run = true): void;
}
