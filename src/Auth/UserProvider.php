<?php

namespace Butschster\Cycle\Auth;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as UserProviderContract;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class UserProvider implements UserProviderContract
{
    private ORMInterface $orm;

    private string $model;

    private Hasher $hasher;

    public function __construct(ORMInterface $orm, string $model, Hasher $hasher)
    {
        $this->orm = $orm;
        $this->model = $model;
        $this->hasher = $hasher;
    }

    /** @inheritDoc */
    public function retrieveById($identifier)
    {
        return $this->getRepository()->findByPK($identifier);
    }

    /** @inheritDoc */
    public function retrieveByToken($identifier, $token)
    {
        $modelInstance = $this->getAuthenticatableInstance();

        return $this->getRepository()->findOne([
            $modelInstance->getAuthIdentifierName() => $identifier,
            $modelInstance->getRememberTokenName()  => $token,
        ]);
    }

    /** @inheritDoc */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $this->getRepository()->persist($user);
    }

    /** @inheritDoc */
    public function retrieveByCredentials(array $credentials)
    {
        $criteria = [];
        foreach ($credentials as $key => $value) {
            if (!Str::contains($key, 'password')) {
                $criteria[$key] = $value;
            }
        }

        return $this->getRepository()->findOne($criteria);
    }

    /** @inheritDoc */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }

    /**
     * Returns instantiated entity.
     *
     * @return Authenticatable
     * @throws ReflectionException
     */
    protected function getAuthenticatableInstance(): Authenticatable
    {
        $refEntity = new ReflectionClass($this->model);

        return $refEntity->newInstanceWithoutConstructor();
    }

    /**
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        return $this->orm->getRepository($this->model);
    }
}
