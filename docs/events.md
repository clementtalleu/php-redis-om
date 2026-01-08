# Events

You can create event listeners in the Doctrine way.

Here is an example without Symfony
```php
<?php declare(strict_types=1);

use Talleu\RedisOm\Event\EventManager;
use Talleu\RedisOm\Event\Events;
use Talleu\RedisOm\Om\RedisObjectManager;

$eventManager = new EventManager();

$eventManager->addEventListener(Events::PRE_PERSIST, new class {
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!method_exists($entity, 'setCreatedAt') || !method_exists($entity, 'getCreatedAt')) {
            return;
        }

        if ($entity->getCreatedAt() === null) {
            $entity->setCreatedAt(new \DateTimeImmutable());
        }
    }
});

$objectManager = new RedisObjectManager(eventManager: $eventManager);
$product = new Product();
$objectManager->persist($product);
$objectManager->flush();
```

And here an example for symfony with AsEventListener attribute
```php
<?php declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Talleu\RedisOm\Event\Events;
use Talleu\RedisOm\Event\LifecycleEventArgs;

final readonly class TimestampableListener
{
    #[AsEventListener(event: Events::PRE_PERSIST, priority: 10)]
    public function onPrePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Vérifier si l'entité a les méthodes nécessaires
        if (!method_exists($entity, 'setCreatedAt') || !method_exists($entity, 'getCreatedAt')) {
            return;
        }

        if ($entity->getCreatedAt() === null) {
            $entity->setCreatedAt(new \DateTimeImmutable());
        }

        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    #[AsEventListener(event: Events::PRE_UPDATE, priority: 10)]
    public function onPreUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        
        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
    }
}
```
