<?php

namespace Butschster\Cycle\Database;

use Closure;
use Cycle\ORM\ORMInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DetectsConcurrencyErrors;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Query\BuilderInterface;
use Throwable;

class Connection implements ConnectionInterface
{
    use DetectsConcurrencyErrors;

    private ORMInterface $ORM;
    private DriverInterface $driver;
    private ConnectionInterface $connection;

    /**
     * @param ConnectionInterface $connection
     * @param ORMInterface $ORM
     */
    public function __construct(ConnectionInterface $connection, ORMInterface $ORM)
    {
        $this->ORM = $ORM;
        $this->driver = $ORM->getFactory()->database()->getDriver();
        $this->connection = $connection;
    }

    /** @inheritDoc */
    public function query(): BuilderInterface
    {
        return $this->driver->getQueryBuilder();
    }

    /** @inheritDoc */
    public function table($table, $as = null)
    {
        return $this->query()->selectQuery('')->from($table);
    }

    /** @inheritDoc */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }

    /** @inheritDoc */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->statement($query, $bindings)->fetchAll();
    }

    /** @inheritDoc */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        $statement = $this->statement($query, $bindings);

        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    /** @inheritDoc */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /** @inheritDoc */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /** @inheritDoc */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /** @inheritDoc */
    public function statement($query, $bindings = [])
    {
        return $this->driver->query($query, $bindings);
    }

    /** @inheritDoc */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->driver->execute($query, $bindings);
    }

    /** @inheritDoc */
    public function unprepared($query)
    {
        return $this->driver->execute($query);
    }

    /** @inheritDoc */
    public function prepareBindings(array $bindings)
    {
        return $this->connection->prepareBindings($bindings);
    }

    /** @inheritDoc */
    public function pretend(Closure $callback)
    {
        return $this->connection->pretend($callback);
    }

    /** @inheritDoc */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction();

            // We'll simply execute the given callback within a try / catch block and if we
            // catch any exception we can rollback this transaction so that none of this
            // gets actually persisted to a database or stored in a permanent fashion.
            try {
                $callbackResult = $callback($this);
            }

                // If we catch an exception we'll rollback this transaction and try again if we
                // are not out of attempts. If we are out of attempts we will just throw the
                // exception back out and let the developer handle an uncaught exceptions.
            catch (Throwable $e) {
                $this->handleTransactionException(
                    $e,
                    $currentAttempt,
                    $attempts
                );

                continue;
            }

            try {
                $this->commit();
            } catch (Throwable $e) {
                $this->handleCommitTransactionException(
                    $e,
                    $currentAttempt,
                    $attempts
                );

                continue;
            }

            return $callbackResult;
        }
    }

    /** @inheritDoc */
    public function beginTransaction()
    {
        $this->driver->beginTransaction();
    }

    /** @inheritDoc */
    public function commit()
    {
        $this->driver->commitTransaction();
    }

    /** @inheritDoc */
    public function rollBack($toLevel = null)
    {
        $this->driver->rollbackTransaction();
    }

    /** @inheritDoc */
    public function transactionLevel()
    {
        return 0;
    }

    /**
     * Handle an exception encountered when running a transacted statement.
     *
     * @param Throwable $e
     * @param int $currentAttempt
     * @param int $maxAttempts
     * @return void
     *
     * @throws Throwable
     */
    protected function handleTransactionException(Throwable $e, $currentAttempt, $maxAttempts)
    {
        // On a deadlock, MySQL rolls back the entire transaction so we can't just
        // retry the query. We have to throw this exception all the way out and
        // let the developer handle it in another way. We will decrement too.
        if ($this->causedByConcurrencyError($e)) {
            throw $e;
        }

        // If there was an exception we will rollback this transaction and then we
        // can check if we have exceeded the maximum attempt count for this and
        // if we haven't we will return and try this query again in our loop.
        $this->rollBack();

        if ($this->causedByConcurrencyError($e) && $currentAttempt < $maxAttempts) {
            return;
        }

        throw $e;
    }

    /**
     * Handle an exception encountered when committing a transaction.
     *
     * @param Throwable $e
     * @param int $currentAttempt
     * @param int $maxAttempts
     * @return void
     *
     * @throws Throwable
     */
    protected function handleCommitTransactionException(Throwable $e, $currentAttempt, $maxAttempts)
    {
        if ($this->causedByConcurrencyError($e) && $currentAttempt < $maxAttempts) {
            return;
        }

        throw $e;
    }

    public function raw($value)
    {
        return new Fragment($value);
    }
}
