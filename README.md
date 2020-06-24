# Cycle ORM integration for the Laravel Framework

**!!! Important note!!!**
At this moment this package works only with postgres database driver.

### Requirements
- Laravel 7.x
- PHP 7.4 and above

## Installation and Configuration

From the command line run
```shell script
composer require butschster/cycle-orm
```

### Env variables

```
DB_CONNECTION=postgres
DB_HOST=127.0.0.1
DB_PORT= # Default port 5432
DB_DATABASE=homestead
DB_USERNAME=root
DB_PASSWORD=

DB_MIGRATIONS_TABLE=migrations # Migrations table name

DB_SCHEMA_SYNC=false # Sync DB schema without migrations
DB_SCHEMA_CACHE=true # Cache DB schema
DB_SCHEMA_CACHE_DRIVER=file # DB schema cache driver
```

### Configuration

Publish the config file.
```shell script
php artisan vendor:publish --provider="Butschster\Cycle\Providers\LaravelServiceProvider" --tag=config
```

**That's it!**

### Console commands

#### php artisan cycle:migrate
Run cycle orm migrations from directory.

#### php artisan cycle:refresh
Refresh database schema.

## Usage
By default, class locator looks for entities in app folder. You can specify locations in `config/cycle.php` config file.

```php
...
'directories' => [
    app_path(),
],
...
```

### Example

#### User
```php
<?php
namespace App;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @ORM\Entity(
 *     table="users",
 *     repository="App\UserRepository",
 * )
 */
class User implements Authenticatable
{
    /** @ORM\Column(type="uuid", primary=true) */
    protected string $id;

    /** @ORM\Column(type="string") */
    protected string $password;

    /** @ORM\Column(type="string") */
    protected string $rememberToken = '';

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * @return string
     */
    public function getAuthIdentifier(): string
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setAuthPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getRememberToken(): string
    {
        return $this->rememberToken;
    }

    /**
     * @param string $value
     */
    public function setRememberToken($value): void
    {
        $this->rememberToken = $value;
    }

    /**
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return 'rememberToken';
    }
}
```

#### Repository
```php
namespace App;

use Butschster\Cycle\Repository;

class UserRepository extends Repository
{
    /** @inheritDoc */
    public function findByUsername(string $username): ?User
    {
        return $this->findOne(['username' => $username]);
    }
}
```

#### Create user
```php
use Cycle\ORM\ORMInterface;
use Butschster\Cycle\Facades\ORM;

$user = new User();
$repository = app(ORMInterface::class)->getRepository($user);
// or
$repository = ORM::getRepository($user);
// or
$repository = ORM::getRepository(User::class);

$repository->persist($user);
```

#### Update user
```php
use Butschster\Cycle\Facades\ORM;

$repository = ORM::getRepository(User::class);
$user = $repository->findByPK('5c9e177b0a975a6eeccf5960');
$user->setAuthPassword('secret');

$repository->persist($user);
```

#### Delete user
```php
use Butschster\Cycle\Facades\ORM;

$repository = ORM::getRepository(User::class);
$user = $repository->findByPK('5c9e177b0a975a6eeccf5960');

$repository->delete($user);
```
