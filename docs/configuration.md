# Configuration

### Persistent connections
> *A Redis server is designed to handle a lot of operations per second making it a very good candidate as a data cache storing engine.
But connecting to the server is an expensive operation and thus connections rate should be kept as low as possible.*.

For more information about persistent connections, read this [article](https://medium.com/assoconnect/how-to-use-persistent-connections-with-redis-for-symfony-cache-with-php-fpm-3e7bd1100736)

php-redis-om provide a way to use persistent connections with the `RedisClient` class.

```php
// Set the persistent connection to true
$objectManager = new RedisObjectManager(createPersistentConnection: true);

// Then you can use the ObjectManager normally
$objectManager->persist($user);
$objectManager->flush();
```

Warning: if you set this parameter to true, the client will connect to the redis server as soon as the object manager is instantiated. Even if the object manager is not actually used.


