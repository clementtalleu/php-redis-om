# php-redis-om, advanced mapping configuration


## Mapping object

Customize the mapping configuration by adding parameters to your `#[RedisOm\Entity]` attribute.

```php
<?php 

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;

#[RedisOm\Entity(
        prefix: 'user_redis',
        format: RedisFormat::JSON->value,
        converter: new MyCustomConverter(),
        repository: new MyCustomRepository(),
        ttl: 3600
)]
class User
{}
```

Each of these parameters are optional and can be omitted. Here is a description of each parameter:

- prefix: 
    - The prefix to use for the keys in Redis. If not set, the class name will be used.
    - Example: `user_redis`
    - Default: `null`
    - Type: `string`
    - Note: The prefix will be concatenated with the id of the object to create the key in Redis.
- format:
    - The format to use to store the object in Redis. If not set, the default format will be used : `HASH`.
    - Example: `RedisFormat::JSON->value` (JSON)
    - Default: `RedisFormat::HASH->value` (HASH)
    - Type: `string`
    - Note: To use the "JSON" format, your redis server must have the Redis JSON module installed.
- ttl:
    - "Time to live", the time (in seconds) before the expiration of the object, leave null to keep your objets for ever.
    - Example: `3600` (seconds)
    - Default: `null` 
    - Type: `integer`
- converter: 
    - The converter to use to convert your objects to and from Redis. If not set, the default converter will be used.
    - Example: `new MyCustomConverter()`
    - Default: `null`
    - Type: `ConverterInterface`
    - Note: The converter must implement the `ConverterInterface` interface.
- repository: 
    - The repository to use to fetch your objects from Redis. If not set, the default repository will be used.
    - Example: `new MyCustomRepository()`
    - Default: `null`
    - Type: `RepositoryInterface`
    - Note: The repository must implement the `RepositoryInterface` interface.

You could alternate from JSON format to HASH format in each entity by setting the format parameter to `RedisFormat::HASH->value` or `RedisFormat::JSON->value`.
But be careful, if you switch the format in an entity that already has data stored in Redis, you will lose all the index stored in the previous format.

Dont forget to run the migration command after changing the format of an entity.

```console
vendor/bin/redisMigration <YOUR DIRECTORY PATH>
```


## Mapping properties

Add the `#[RedisOm\Property]` attribute to persist the property in Redis.
Inject an index parameter to make the property queryable.

```php
<?php 

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;

#[RedisOm\Entity]
class User
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public int $id;

    #[RedisOm\Property(
        index: true,
        getter: 'obtainName',
        setter: 'withName',
    )]
    public string $name;

    #[RedisOm\Property(index: ['age_numeric' => Property::INDEX_NUMERIC, 'age_text' => Property::INDEX_TEXT])]
    public int $age;
    
    #[RedisOm\Property]
    public ?string $description;

    #[RedisOm\Property(index: ['createdAt#timestamp' => RedisOm\Property::INDEX_NUMERIC])]
    private ?DateTimeInterface $createdAt = null;
}
```

Each of these parameters are optional and can be omitted. Here is a description of each parameter:

- index:
    - The index in Redis. If not set, the property is false by default and could not be queryable.
    - Example: `true` Creates default indexes depending on the property type:
        - `string` / object : TAG + TEXT
        - `int` / `float` : TAG + NUMERIC
        - `DateTime` : TAG + TEXT + NUMERIC
    - Example: `['age' => Property::INDEX_NUMERIC]`
    - Default: `false`
    - Type: `boolean | array`
- getter:
    - The name of the getter method to use to get the value of the property **if the property is not public**. If not set, a default getter as : `getName()` will be used.
    - Example: `obtainName`
    - Default: `null`
    - Type: `string`
- setter: 
    - The name of the setter method to use to set the value of the property **if the property is not public**. If not set, a default setter as : `setName()` will be used.
    - Example: `withName`
    - Default: `null`
    - Type: `string`

The #[RedisOm\Id] attribute is by default indexed and could be requested.

## Unique constraints

`#[Unique]` guarantees that no two objects of the same class share the same value for a given field (or combination of fields) at the time of `flush()`.

### Single-field constraint

Place `#[RedisOm\Unique]` on a property alongside `#[RedisOm\Property]`:

```php
<?php

use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
class User
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public int $id;

    #[RedisOm\Property(index: true)]
    #[RedisOm\Unique]
    public string $email;

    #[RedisOm\Property]
    public string $name;
}
```

Attempting to persist two objects with the same email throws on `flush()`:

```php
use Talleu\RedisOm\Exception\UniqueConstraintViolationException;

$alice = new User(); $alice->id = 1; $alice->email = 'alice@example.com';
$bob   = new User(); $bob->id   = 2; $bob->email   = 'alice@example.com'; // duplicate

$objectManager->persist($alice);
$objectManager->persist($bob);

try {
    $objectManager->flush();
} catch (UniqueConstraintViolationException $e) {
    echo $e->getMessage();
    // Unique constraint violation on App\Entity\User::email, value "alice@example.com" already exists.
}
```

### Composite constraint

Place `#[RedisOm\Unique(properties: [...])]` at the **class level** to enforce uniqueness on a combination of fields.
The attribute is repeatable, so multiple independent constraints can be declared on the same class.

```php
<?php

use Talleu\RedisOm\Om\Mapping as RedisOm;

#[RedisOm\Entity]
#[RedisOm\Unique(properties: ['username', 'tenantId'])]
#[RedisOm\Unique(properties: ['email',    'tenantId'])]
class User
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public int $id;

    #[RedisOm\Property]
    public string $username;

    #[RedisOm\Property]
    public int $tenantId;

    #[RedisOm\Property]
    public string $email;
}
```

The same `username` is allowed across different tenants; only the combination `(username, tenantId)` must be unique:

```php
// OK: same username, different tenants
$u1 = new User(); $u1->id = 1; $u1->username = 'john'; $u1->tenantId = 1;
$u2 = new User(); $u2->id = 2; $u2->username = 'john'; $u2->tenantId = 2;
$objectManager->persist($u1);
$objectManager->persist($u2);
$objectManager->flush(); // succeeds

// NOT OK: duplicate combination
$u3 = new User(); $u3->id = 3; $u3->username = 'john'; $u3->tenantId = 1;
$objectManager->persist($u3);
$objectManager->flush(); // throws UniqueConstraintViolationException
// Unique constraint violation on App\Entity\User: combination (tenantId="1", username="john") already exists.
```

### Behavior during merge and remove

**merge():** when you load an object via `find()`, change a unique field, and call `merge()`, the library detects the change, deletes the old unique key and claims the new one — atomically. If the new value is already taken, `flush()` throws.

```php
$user = $objectManager->find(User::class, 1); // email = 'old@example.com'
$user->email = 'new@example.com';

$objectManager->merge($user);
$objectManager->flush(); // old key released, new key claimed
```

**remove():** unique keys are deleted inside the same transaction as the object, so the value becomes immediately available for another object:

```php
$objectManager->remove($user);
$objectManager->flush(); // unique key released

$other->email = $user->email;
$objectManager->persist($other);
$objectManager->flush(); // succeeds
```

### Concurrency guarantee

`flush()` uses Redis [WATCH][watch] + [MULTI][multi]/[EXEC][exec] to prevent race conditions: all relevant unique keys are watched before the transaction, checked for collisions, then written atomically. If a concurrent process claims the same key between `WATCH` and `EXEC`, the transaction is aborted and `UniqueConstraintViolationException::concurrentModification()` is thrown.

### Limitations

- Violations are detected only at `flush()` time, not at `persist()` or `merge()` time.
- Unique fields must be scalar values (string, int, float). Arrays and nested objects are not supported.
- `#[Unique]` does not imply `#[Property(index: true)]`. Add the index separately if you need to query by that field.
- The library does not scan existing data when `#[Unique]` is added to an existing class. Run your own deduplication before deploying the constraint.

[watch]: https://redis.io/docs/latest/commands/watch/
[multi]: https://redis.io/docs/latest/commands/multi/
[exec]:  https://redis.io/docs/latest/commands/exec/

## Update the schema
After each modification of your classes, you have to update the schema in Redis. You can do it by running the following command:

```console
vendor/bin/redisMigration <YOUR DIRECTORY PATH>
```
the default path is `./src`.
