# Advanced usage

If you're familiar with Doctrine, you'll feel right at home with php-redis-om.
The library provides a set of tools to help you manage your Redis objects in a more efficient way.

You can use the `RedisObjectManager` class to persist, remove, and retrieve objects from Redis.
```php
$objectManager = new RedisObjectManager(); // For Symfony users directly inject RedisObjectManagerInterface in your constructor

// Add the object to the object manager to be persisted on flush
$objectManager->persist($user);

// Will remove the object from the object manager, so it won't be persisted on flush 
$objectManager->detach($user); 

// Will remove all objects from the object manager
$objectManager->clear(); 

// Will remove the object from Redis on flush
$objectManager->remove($user); 

// Will refresh the object from the redis state (updates the object in place)
$objectManager->refresh($user);

// Will merge the object: only persist properties that changed since the last find()
// Falls back to a full persist if the object was not loaded via find()
$objectManager->merge($user);

// Check if the object is managed by the object manager
$objectManager->contains($user); 

// Get the datetime of an object's expiration (or null if no expiration)
$objectManager->getExpirationTime($user); 
```

### Handling unique constraint violations

If any entity has a `#[Unique]` constraint, `flush()` may throw `UniqueConstraintViolationException`. Wrap it where needed:

```php
use Talleu\RedisOm\Exception\UniqueConstraintViolationException;

try {
    $objectManager->persist($user);
    $objectManager->flush();
} catch (UniqueConstraintViolationException $e) {
    // $e->getMessage() describes which field(s) and value(s) conflicted
}
```

See [mapping.md](mapping.md#unique-constraints) for the full reference: composite constraints, merge/remove behavior, concurrency guarantees, and limitations.

You can also retrieve and query your objects with the ObjectManager or a given repository
```php
$objectManager = new RedisObjectManager(); // For Symfony users directly inject RedisObjectManagerInterface in your constructor

// Will retrieve the object from Redis by giving class and identifier
$objectManager->find(User::class, $id); 

// Will retrieve a repository for the given class then you can use the repository to query your objects
$userRepository = $objectManager->getRepository(User::class); 

// Will retrieve all your users stored in Redis
$userRepository->findAll();

// Will retrieve 1 user with the name 'John Doe'
$userRepository->findOneBy(['name' => 'John Doe']); 

// Will retrieve all users with the name 'John'
$userRepository->findBy(['name' => 'John']); 

// Will retrieve all users with the name 'John' sorted by age in ascending order
$userRepository->findBy(['name' => 'John'], ['age' => 'ASC']);

// Will retrieve 5 users with the name 'John' sorted by age in ascending order
$userRepository->findBy(['name' => 'John'], ['age' => 'ASC'], 5); 

// Will retrieve all users with a field containing 'John', whatever the field. Second parameter is the limit of results (optional)
$userRepository->findLike('John', 5); 

// Will retrieve 1 user with the name contains "jo" : 'John Doe', 'Johnny', 'Dalton joe'
$userRepository->findOneByLike(['name' => 'jo']); 

// Will retrieve all users with the name contains "do" : 'John Doe', 'just do it', 'dodo la saumure'...
$userRepository->findByLike(['name' => 'do']); 

// Will retrieve an integer representing the number of users with the name 'John'
$userRepository->count(['name' => 'John']); 

// Will retrieve only the property "name" of the object for the id 3.
$userRepository->getPropertyValue(identifier: 3, property: 'name'); 
// ⚠️ Warning: this method cannot retrieve array or nested objects when HASH format

// Will retrieve multiple objects by their identifiers in a single pipeline round-trip
$userRepository->findMultiple([1, 2, 3]);

// Will retrieve all users whose name starts with 'Jo'
$userRepository->findByStartWith(['name' => 'Jo']);

// Will retrieve all users whose name ends with 'Doe'
$userRepository->findByEndWith(['name' => 'Doe']);

// Will count all users whose name contains 'John'
$userRepository->countByLike(['name' => 'John']);

// Will paginate results: returns a Paginator with items and total count
$paginator = $userRepository->paginate(criteria: ['name' => 'John'], page: 1, itemsPerPage: 20);

// Will retrieve users within 10km of a given geographic point
$userRepository->findByGeoRadius(
    geoField: 'location',
    longitude: 2.3522,
    latitude: 48.8566,
    radius: 10,
    unit: 'km'
);
```

#### You can also request objects or collection by nested objects properties
```php
// Will retrieve 1 user from the category called 'CUSTOMER'
$userRepository->findOneBy(['category_name' => 'CUSTOMER']); 

// Will retrieve all users from the category 3
$userRepository->findBy(['category_id' => 3]); 
```

#### Request by date (DateTimeInterface or string)
```php

// Will retrieve users from datetime 
$userRepository->findOneBy(['createdAt' => new DateTime('2021-01-01 00:00:00')]); 
$userRepository->findBy(['createdAt' => new DateTime('2021-01-01 00:00:00')]); 
 
// Will retrieve users by datetime as string
$userRepository->findOneBy(['createdAt' => '2021-01-01 00:00:00']); 
$userRepository->findBy(['createdAt' => '2021-01-01 00:00:00']); 
```

## Streaming large collections

`findAll()` loads the full collection in memory. For large datasets, use `stream()` which fetches objects in batches and yields them one by one, keeping memory bounded.

### Via the repository

```php
// All objects, default batch size of 100
foreach ($repository->stream() as $user) {
    // process $user
}

// With criteria and custom batch size
foreach ($repository->stream(['active' => true], batchSize: 500) as $user) {
    // process $user
}

// With ordering
foreach ($repository->stream(orderBy: ['createdAt' => 'ASC']) as $user) {
    // process $user
}
```

`break` works normally — the remaining batches are never fetched.

Memory management is manual. If you call `merge()` inside the loop, the identity map grows by one entry per object. For long-running jobs, call `$objectManager->clear()` periodically to release it:

```php
foreach ($repository->stream(batchSize: 500) as $i => $user) {
    // process...
    if ($i % 500 === 499) {
        $objectManager->flush();
        $objectManager->clear();
    }
}
```

### Via the object manager (auto-clear)

`RedisObjectManager::stream()` clears the identity map automatically after each batch, keeping memory flat with no manual intervention:

```php
foreach ($objectManager->stream(User::class, ['active' => true]) as $user) {
    // process $user
}
```

Two constraints to keep in mind:

- Do not call `merge()` on an object once the generator has advanced past its batch — the snapshot is gone and the merge will silently fall back to a full persist.
- Any pending operations not yet flushed are discarded at each batch boundary. Call `flush()` before streaming if you have queued writes.

## Repository

You can create your own repository to query your objects in Redis. Then inject it in the
`#[RedisOm\Entity(repository: YourCustomRepository::class)]` attribute to use it.

Then in each custom repository you can add custom methods to query your objects in Redis.


## QueryBuilder

You can instantiate a QueryBuilder to create, write and run your own complex queries.
All this while respecting [redis command line syntax](https://redis.io/docs/latest/commands/ft.search/). 

For example :
```php
    $repository = $objectManager->getRepository(Foo::class);
    
    // Will retrieve all objects with the age 20 or 34
    $queryBuilder = $repository->createQueryBuilder();
    $queryBuilder->query('@age:{20 | 34}');
    $results = $queryBuilder->execute();

    // Will retrieve all objects starts with 'foo'
    $queryBuilder = $repository->createQueryBuilder();
    $queryBuilder->query('@age:{foo*}');
    $results = $queryBuilder->execute();
```