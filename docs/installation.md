# Installation

### PHP
To use the library you need a php-version >= 8.2 and the Redis extension installed.
If you don't have the Redis extension installed, you can use your favorite Redis client (for example Predis),
and create a new class that implements the `RedisClientInterface` interface, then inject it directly in the `#[RedisOm\Entity(client: YourRedisClient::class)]` attribute.


### Redis
You need a Redis server with a version >= 4.0. If you want to use the [JSON](https://redis.io/docs/latest/develop/data-types/json/) data type 
you need a Redis server with the JSON module installed. By default, the library uses the HASH format type to store objects in Redis, which does not need any module.
But for indexing our objects and create the schema, you will need the [Redisearch](https://redis.io/search/) module installed.

We recommend using the [Redis stack](https://redis.io/about/about-stack/) to get all modules you need.

### Composer

Install the library by running the following command:

```console
composer require talleu/php-redis-om
```

Or add the library to your `composer.json` file:

```json
{
    "require": {
        "talleu/php-redis-om": "*"
    }
}
```

Then run `composer update` to install the library.