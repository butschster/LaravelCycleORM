<?php

namespace Butschster\Cycle\Database;

use Cycle\ORM\ORMInterface;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory as BaseFactory;

class Factory extends BaseFactory
{
    protected ORMInterface $orm;

    /**
     * Create a new factory instance.
     *
     * @param ORMInterface $orm
     * @param Faker $faker
     */
    public function __construct(ORMInterface $orm, Faker $faker)
    {
        parent::__construct($faker);
        $this->orm = $orm;
    }

    /**
     * Create a builder for the given model.
     *
     * @param string $class
     * @return FactoryBuilder
     */
    public function of($class): FactoryBuilder
    {
        return new FactoryBuilder(
            $class,
            $this->definitions,
            $this->states,
            $this->afterMaking,
            $this->afterCreating,
            $this->orm,
            $this->faker
        );
    }
}
