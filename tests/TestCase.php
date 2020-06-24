<?php


namespace Butschster\Tests;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\TransactionInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Mockery as m;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return Repository|m\MockInterface
     */
    protected function makeConfig()
    {
        return m::mock(Repository::class);
    }

    /**
     * @return ORMInterface|m\MockInterface
     */
    protected function mockOrm()
    {
        return m::mock(ORMInterface::class);
    }

    /**
     * @return TransactionInterface|m\MockInterface
     */
    protected function mockTransaction()
    {
        return m::mock(TransactionInterface::class);
    }

    /**
     * @return Application|m\MockInterface
     */
    protected function makeApplication()
    {
        return m::spy(Application::class);
    }
}
