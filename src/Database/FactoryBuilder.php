<?php

namespace Butschster\Cycle\Database;

use Cycle\ORM\ORMInterface;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\FactoryBuilder as BaseFactoryBuilder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laminas\Hydrator\ReflectionHydrator;
use ReflectionClass;
use ReflectionException;

class FactoryBuilder extends BaseFactoryBuilder
{
    protected ORMInterface $orm;

    /**
     * Create an new builder instance.
     *
     * @param string $class
     * @param array $definitions
     * @param array $states
     * @param array $afterMaking
     * @param array $afterCreating
     * @param ORMInterface $orm
     * @param Faker $faker
     */
    public function __construct(string $class, array $definitions, array $states, array $afterMaking, array $afterCreating, ORMInterface $orm, Faker $faker)
    {
        parent::__construct($class, $definitions, $states, $afterMaking, $afterCreating, $faker);

        $this->orm = $orm;
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param array $attributes
     * @return Collection|mixed
     */
    public function create(array $attributes = [])
    {
        $results = $this->make($attributes, true);

        if ($results instanceof Collection) {
            $this->store($results);

            $this->callAfterCreating($results);
        } else {
            $this->store(collect([$results]));

            $this->callAfterCreating(collect([$results]));
        }

        return $results;
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param iterable $records
     * @return Collection|mixed
     */
    public function createMany(iterable $records)
    {
        return collect($records)->map(function ($attributes) {
            return $this->create($attributes);
        });
    }

    /**
     * Create a collection of models.
     *
     * @param array $attributes
     * @param bool $persist
     * @return Collection|mixed
     * @throws ReflectionException
     */
    public function make(array $attributes = [], bool $persist = false)
    {
        if ($this->amount === null) {
            return tap($this->makeInstance($attributes, $persist), function ($instance) {
                $this->callAfterMaking(collect([$instance]));
            });
        }

        if ($this->amount < 1) {
            return collect();
        }

        $instances = collect(range(1, $this->amount))->map(function () use ($attributes, $persist) {
            return $this->makeInstance($attributes, $persist);
        });

        $this->callAfterMaking($instances);

        return $instances;
    }

    /**
     * Set the connection name on the results and store them.
     *
     * @param Collection $results
     * @return void
     */
    protected function store($results)
    {
        $results->map(function ($entity) {
            $repository = $this->orm->getRepository($this->class);

            $repository->persist($entity);

            return $entity;
        });
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param array $attributes
     * @param bool $persist
     * @return object
     * @throws ReflectionException
     */
    protected function makeInstance(array $attributes = [], bool $persist = false)
    {
        $hydrator = new ReflectionHydrator();
        $object = (new ReflectionClass($this->class))->newInstanceWithoutConstructor();

        return $hydrator->hydrate(
            $this->getRawAttributes($attributes, $persist),
            $object
        );
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param array $attributes
     * @param bool $persist
     * @return mixed
     *
     */
    protected function getRawAttributes(array $attributes = [], bool $persist = false)
    {
        if (!isset($this->definitions[$this->class])) {
            throw new InvalidArgumentException("Unable to locate factory for [{$this->class}].");
        }

        $definition = call_user_func(
            $this->definitions[$this->class],
            $this->faker,
            $attributes
        );

        return $this->expandAttributes(
            array_merge($this->applyStates($definition, $attributes), $attributes),
            $persist
        );
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param array $attributes
     * @param bool $persist
     * @return array
     */
    protected function expandAttributes(array $attributes, bool $persist = false)
    {
        foreach ($attributes as &$attribute) {
            if (is_callable($attribute) && !is_string($attribute) && !is_array($attribute)) {
                $attribute = $attribute($attributes);
            }

            if ($attribute instanceof static) {
                if ($persist) {
                    $attribute = $attribute->create();
                } else {
                    $attribute = $attribute->make();
                }
            }
        }

        return $attributes;
    }
}
