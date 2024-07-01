# php-redis-om, advanced mapping configuration


## Mapping object

Customize the mapping configuration by adding parameters to your `#[RedisOm\Entity]` attribute.

```php
<?php 

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;

#[RedisOm\Entity(
        prefix: 'user_redis',
        expires: 12000,
        format: RedisFormat::JSON->value,
        persister: new MyCustomPersister(),
        converter: new MyCustomConverter(),
        repository: new MyCustomRepository(),
        redisClient: new MyCustomRedisClient(),
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
- expires:
    - The time in seconds before the entry expires in redis. If not set, the key will never expire.
    - Example: `12000`
    - Default: `null`
    - Type: `int`
    - Note: The key will be set to expire in the given time after the last write operation.
- format:
    - The format to use to store the object in Redis. If not set, the default format will be used : `HASH`.
    - Example: `RedisFormat::JSON->value` (JSON)
    - Default: `RedisFormat::HASH->value` (HASH)
    - Type: `string`
    - Note: To use the "JSON" format, your redis server must have the Redis JSON module installed.
- persister:
    - The persister to use to persist your objects in Redis. If not set, the default persister will be used.
    - Example: `new MyCustomPersister()`
    - Default: `null`
    - Type: `PersisterInterface`
    - Note: The persister must implement the `PersisterInterface` interface.
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
- redisClient: 
    - The redis client to use to connect to your Redis server. If not set, the default redis client will be used.
    - Example: `new MyCustomRedisClient()`
    - Default: `null`
    - Type: `RedisClientInterface`
    - Note: The redis client must implement the `RedisClientInterface` interface, it could be a php-redis client 
or any other client that implements the interface.

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

    #[RedisOm\Property(
        index: ['age_numeric' => Property::INDEX_NUMERIC, 'age_text' => Property::INDEX_TEXT],
    )]
    public int $age;
    
    #[RedisOm\Property]
    public ?string $description;
}
```

Each of these parameters are optional and can be omitted. Here is a description of each parameter:

- index:
    - The index in Redis. If not set, the property is false by default and could not be queryable.
    - Example: `true` Will create default index (TAG + TEXT)
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

## Update the schema
After each modification of your classes, you have to update the schema in Redis. You can do it by running the following command:

```console
vendor/bin/redisMigration <YOUR DIRECTORY PATH>
```
the default path is `./src`.