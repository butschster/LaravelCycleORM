# Cycle ORM integration for the Laravel Framework
Cycle is a PHP DataMapper ORM and Data Modelling engine designed to safely work in classic and daemonized PHP 
applications (like RoadRunner). The ORM provides flexible configuration options to model datasets, a powerful query
builder, and supports dynamic mapping schemas. The engine can work with plain PHP objects, support annotation
declarations, and proxies via extensions.

Full information - https://cycle-orm.dev/docs


[![Latest Stable Version](https://poser.pugx.org/butschster/cycle-orm/v/stable)](https://packagist.org/packages/butschster/cycle-orm) [![Total Downloads](https://poser.pugx.org/butschster/cycle-orm/downloads)](https://packagist.org/packages/butschster/cycle-orm) [![License](https://poser.pugx.org/butschster/cycle-orm/license)](https://packagist.org/packages/butschster/cycle-orm)

### Requirements
- Laravel 7.x
- PHP 7.4 and above

## Installation and Configuration

From the command line run
```shell script
composer require butschster/cycle-orm
```

Optionally you can register the EntityManager, Transaction and/or ORM facade:
```
'DatabaseManager' => Butschster\Cycle\Facades\DatabaseManager::class,
'Transaction' => Butschster\Cycle\Facades\Transaction::class,
'ORM' => Butschster\Cycle\Facades\ORM::class,
'EntityManager' => Butschster\Cycle\Facades\EntityManager::class,
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

#### Configure Databases
The list of available connections and databases can be listed in the `config/cycle.php` - `database` section. 
For more information see https://cycle-orm.dev/docs/basic-connect#configure-databases

#### Getting Database Manager ($dbal)
`DatabaseManager` registered as a singleton container 

```
$dbal = $this->app->get(\Spiral\Database\DatabaseManager::class);
// Or
$dbal = $this->app->get(\Spiral\Database\DatabaseProviderInterface::class);
```


**That's it!**

### Console commands

#### php artisan cycle:migrate
Run cycle orm migrations from the directory.

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

### Entity Manager
The EntityManager is the central access point to ORM functionality. It can be used to find, persist and remove entities.

#### Using the EntityManager
You can use the facade,  container or Dependency injection to access the EntityManager methods
```php
EntityManager::persist($entity);

// Or

app(\Butschster\Cycle\Contracts\EntityManager::class)->persist($entity);

// Or

use Butschster\Cycle\Contracts\EntityManager;

class ExampleController extends Controller
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
}
```

#### Finding entities
Entities are objects with identity. Their identity has a conceptual meaning inside your domain. In a CMS application 
each article has a unique id. You can uniquely identify each article by that id.

```php
$article = EntityManager::findByPK('App\Article', 1);
$article->setTitle('Different title');

$article2 = EntityManager::findByPK('App\Article', 1);

if ($article === $article2) {
    echo "Yes we are the same!";
}
```

#### Persisting
By passing the entity through the persist method of the EntityManager, that entity becomes MANAGED, which means that
its persistence is from now on managed by an EntityManager. 

```php
$article = new Article;
$article->setTitle('Let\'s learn about persisting');

EntityManager::persist($article);
```

#### Deleting
An entity can be deleted from persistent storage by passing it to the delete($entity) method.
```php
EntityManager::delete($article);
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
use Butschster\Cycle\Facades\EntityManager;

$user = new User();

$repository = app(ORMInterface::class)->getRepository($user);
// or
$repository = ORM::getRepository($user);
// or
$repository = ORM::getRepository(User::class);

$repository->persist($user);

// or

EntityManager::persist($user);
```

#### Update user
```php
use Butschster\Cycle\Facades\ORM;
use Butschster\Cycle\Facades\EntityManager;

$repository = ORM::getRepository(User::class);
$user = $repository->findByPK('5c9e177b0a975a6eeccf5960');
$user->setAuthPassword('secret');

$repository->persist($user);

// or

EntityManager::persist($user);
```

#### Delete user
```php
use Butschster\Cycle\Facades\ORM;
use Butschster\Cycle\Facades\EntityManager;

$repository = ORM::getRepository(User::class);
$user = $repository->findByPK('5c9e177b0a975a6eeccf5960');

$repository->delete($user);

// or

EntityManager::delete($user);
```
