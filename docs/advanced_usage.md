# Advanced usage

If you're familiar with Doctrine, you'll feel right at home with php-redis-om.
The library provides a set of tools to help you manage your Redis objects in a more efficient way.

You can use the `RedisObjectManager` class to persist, remove, and retrieve objects from Redis.
```php
$objectManager = new RedisObjectManager();

$objectManager->persist($user); // Add the object to the object manager to be persisted on flush
$objectManager->detach($user); // Will remove the object from the object manager, so it won't be persisted on flush
$objectManager->clear(); // Will remove all objects from the object manager
$objectManager->remove($user); // Will remove the object from Redis on flush
$objectManager->refresh($user); // Will refresh the object from the redis state
```

You can also retrieve and query your objects with the ObjectManager or a given repository
```php
$objectManager = new RedisObjectManager();

$objectManager->find(User::class, $id); // Will retrieve the object from Redis by giving class and identifier
$userRepository = $objectManager->getRepository(User::class); // Will retrieve a repository for the given class then you can use the repository to query your objects

$userRepository->findAll(); // Will retrieve all your users stored in Redis
$userRepository->findOneBy(['name' => 'John Doe']); // Will retrieve 1 user with the name 'John Doe'
$userRepository->findBy(['name' => 'John']); // Will retrieve all users with the name 'John'
$userRepository->findBy(['name' => 'John'], ['age' => 'ASC']); // Will retrieve all users with the name 'John' sorted by age in ascending order
$userRepository->findBy(['name' => 'John'], ['age' => 'ASC'], 5); // Will retrieve 5 users with the name 'John' sorted by age in ascending order
$userRepository->count(['name' => 'John']); // Will retrieve an integer representing the number of users with the name 'John'
```

## Repository

You can create your own repository to query your objects in Redis. Then inject it in the
`#[RedisOm\Entity(repository: YourCustomRepository::class)]` attribute to use it.

Then in each custom repository you can add custom methods to query your objects in Redis.

