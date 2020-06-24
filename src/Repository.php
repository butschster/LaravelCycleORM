<?php

namespace Butschster\Cycle;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository as BaseRepository;
use Cycle\ORM\Transaction;
use Cycle\ORM\TransactionInterface;
use Illuminate\Support\Collection;
use Spiral\Pagination\Paginator as SpiralPaginator;
use Throwable;

class Repository extends BaseRepository
{
    private TransactionInterface $transaction;

    /**
     * @param Select $select
     * @param TransactionInterface $transaction
     */
    public function __construct(Select $select, TransactionInterface $transaction)
    {
        $this->transaction = $transaction;

        parent::__construct($select);
    }

    /**
     * Find multiple entities using given scope and sort options.
     *
     * @param array $scope
     * @param array $orderBy
     *
     * @return Collection
     */
    public function findAll(array $scope = [], array $orderBy = []): Collection
    {
        return $this->newCollection(
            parent::findAll($scope, $orderBy)
        );
    }

    /**
     * Paginate multiple entities using given scope and sort options
     *
     * @param array $scope
     * @param array $orderBy
     * @param int $perPage
     * @param int $page
     * @param string $pageName
     * @return Paginator
     */
    public function paginate(array $scope = [], array $orderBy = [], int $perPage = 20, int $page = 1, $pageName = 'page'): Paginator
    {
        return $this->paginateQuery(
            $this->select()->where($scope)->orderBy($orderBy),
            $page,
            $perPage,
            $pageName,
        );
    }

    /**
     * Persist the entity.
     *
     * @param object $entity
     * @param bool $cascade
     * @param bool $run Commit transaction
     *
     * @throws Throwable
     */
    public function persist($entity, bool $cascade = true, bool $run = true): void
    {
        $this->transaction->persist(
            $entity,
            $cascade ? Transaction::MODE_CASCADE : Transaction::MODE_ENTITY_ONLY
        );

        if ($run) {
            $this->transaction->run(); // transaction is clean after run
        }
    }

    /**
     * Delete entity from the database.
     *
     * @param $entity
     * @param bool $cascade
     * @param bool $run
     * @throws Throwable
     */
    public function delete($entity, bool $cascade = true, bool $run = true)
    {
        $this->transaction->delete(
            $entity,
            $cascade ? Transaction::MODE_CASCADE : Transaction::MODE_ENTITY_ONLY
        );

        if ($run) {
            $this->transaction->run(); // transaction is clean after run
        }
    }

    protected function paginateQuery(Select $query, $perPage = 20, int $page = 1, $pageName = 'page'): Paginator
    {
        return new Paginator(
            (new SpiralPaginator($perPage))->withPage($page)->paginate($query),
            $this->newCollection($query->fetchAll()),
            $pageName,
        );
    }

    /**
     * Create a new collection of entities
     *
     * @param iterable $items
     * @return Collection
     */
    protected function newCollection(iterable $items): Collection
    {
        return new Collection($items);
    }

}
