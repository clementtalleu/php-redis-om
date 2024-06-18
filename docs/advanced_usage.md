# Advanced usage

If you're familiar with Doctrine, you'll feel right at home with php-redis-om.
The library provides a set of tools to help you manage your Redis objects in a more efficient way.

You can use the `RedisObjectManager` class to persist, remove, and retrieve objects from Redis.
```php
$objectManager = new RedisObjectManager();

// Add the object to the object manager to be persisted on flush
$objectManager->persist($user);

// Will remove the object from the object manager, so it won't be persisted on flush 
$objectManager->detach($user); 

// Will remove all objects from the object manager
$objectManager->clear(); 

// Will remove the object from Redis on flush
$objectManager->remove($user); 

// Will refresh the object from the redis state
$objectManager->refresh($user);

// Check if the object is managed by the object manager
$objectManager->contains($user); 
```

You can also retrieve and query your objects with the ObjectManager or a given repository
```php
$objectManager = new RedisObjectManager();

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

// Will retrieve an integer representing the number of users with the name 'John'
$userRepository->count(['name' => 'John']); 

// Will retrieve only the property "name" of the object for the id 3.
$userRepository->getPropertyValue(identifier: 3, property: 'name'); 
// ⚠️ Warning: this method cannot retrieve array or nested objects when HASH format
```

You can also request objects or collection by nested objects properties
```php
// Will retrieve 1 user from the category called 'CUSTOMER'
$userRepository->findOneBy(['category_name' => 'CUSTOMER']); 

// Will retrieve all users from the category 3
$userRepository->findBy(['category_id' => 3]); 
```

## Repository

You can create your own repository to query your objects in Redis. Then inject it in the
`#[RedisOm\Entity(repository: YourCustomRepository::class)]` attribute to use it.

Then in each custom repository you can add custom methods to query your objects in Redis.

