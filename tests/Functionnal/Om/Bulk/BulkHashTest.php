<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Bulk;

use Talleu\RedisOm\Exception\BulkOperationException;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\CompositeUniqueHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\UniqueHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class BulkHashTest extends RedisAbstractTestCase
{
    private RedisObjectManager $om;

    protected function setUp(): void
    {
        parent::setUp();
        $this->om = new RedisObjectManager(self::createRedisClient());
        static::emptyRedis();
        static::generateIndex();
    }

    // -------------------------------------------------------------------------
    // bulkDelete — no unique constraints
    // -------------------------------------------------------------------------

    public function testBulkDeleteWithoutUniqueDeletesMatchingObjects(): void
    {
        static::loadRedisFixtures(DummyHash::class);

        $repo = $this->om->getRepository(DummyHash::class);
        $deleted = $repo->bulkDelete(['name' => 'Olivier']);

        $this->assertSame(2, $deleted);
        $this->assertSame(0, $repo->count(['name' => 'Olivier']));
        $this->assertSame(1, $repo->count(['name' => 'Kevin']));
    }

    public function testBulkDeleteAllWithoutUniqueRemovesEverything(): void
    {
        static::loadRedisFixtures(DummyHash::class);

        $repo = $this->om->getRepository(DummyHash::class);
        $deleted = $repo->bulkDelete();

        $this->assertSame(3, $deleted);
        $this->assertSame(0, $repo->count());
    }

    public function testBulkDeleteEmptyCollectionReturnsZero(): void
    {
        $repo = $this->om->getRepository(DummyHash::class);
        $deleted = $repo->bulkDelete();

        $this->assertSame(0, $deleted);
    }

    // -------------------------------------------------------------------------
    // bulkDelete — single-field #[Unique]
    // -------------------------------------------------------------------------

    public function testBulkDeleteReleasesUniqueKeys(): void
    {
        $user1 = $this->makeUniqueHash(1, 'alice@example.com', 'Alice');
        $user2 = $this->makeUniqueHash(2, 'bob@example.com', 'Bob');
        $this->om->persist($user1);
        $this->om->persist($user2);
        $this->om->flush();

        $repo = $this->om->getRepository(UniqueHash::class);
        $deleted = $repo->bulkDelete();

        $this->assertSame(2, $deleted);

        // Unique keys must be released so the same emails can be reused
        $om2 = new RedisObjectManager(self::createRedisClient());
        $new1 = $this->makeUniqueHash(10, 'alice@example.com', 'Alice2');
        $new2 = $this->makeUniqueHash(11, 'bob@example.com', 'Bob2');
        $om2->persist($new1);
        $om2->persist($new2);
        $om2->flush();

        $this->assertNotNull($om2->find(UniqueHash::class, 10));
        $this->assertNotNull($om2->find(UniqueHash::class, 11));
    }

    // -------------------------------------------------------------------------
    // bulkDelete — composite #[Unique]
    // -------------------------------------------------------------------------

    public function testBulkDeleteReleasesCompositeUniqueKeys(): void
    {
        $user1 = $this->makeCompositeHash(1, 'john', 1, 'john@t1.com');
        $user2 = $this->makeCompositeHash(2, 'john', 2, 'john@t2.com');
        $this->om->persist($user1);
        $this->om->persist($user2);
        $this->om->flush();

        $repo = $this->om->getRepository(CompositeUniqueHash::class);
        $deleted = $repo->bulkDelete(['username' => 'john']);

        $this->assertSame(2, $deleted);

        // Composite unique keys released: same username+tenantId combos must be reusable
        $om2 = new RedisObjectManager(self::createRedisClient());
        $new1 = $this->makeCompositeHash(10, 'john', 1, 'john@t1-new.com');
        $new2 = $this->makeCompositeHash(11, 'john', 2, 'john@t2-new.com');
        $om2->persist($new1);
        $om2->persist($new2);
        $om2->flush();

        $this->assertNotNull($om2->find(CompositeUniqueHash::class, 10));
        $this->assertNotNull($om2->find(CompositeUniqueHash::class, 11));
    }

    // -------------------------------------------------------------------------
    // bulkUpdate — non-unique fields
    // -------------------------------------------------------------------------

    public function testBulkUpdateNonUniqueFieldUpdatesMatchingObjects(): void
    {
        static::loadRedisFixtures(DummyHash::class);

        $repo = $this->om->getRepository(DummyHash::class);
        $updated = $repo->bulkUpdate(['name' => 'Olivier'], ['age' => 99]);

        $this->assertSame(2, $updated);

        $om2 = new RedisObjectManager(self::createRedisClient());
        $repo2 = $om2->getRepository(DummyHash::class);
        foreach ($repo2->findBy(['name' => 'Olivier']) as $obj) {
            $this->assertSame(99, $obj->age);
        }
    }

    public function testBulkUpdateLeavesNonMatchingObjectsUntouched(): void
    {
        static::loadRedisFixtures(DummyHash::class);

        $repo = $this->om->getRepository(DummyHash::class);
        $repo->bulkUpdate(['name' => 'Olivier'], ['age' => 99]);

        $om2 = new RedisObjectManager(self::createRedisClient());
        $kevin = $om2->getRepository(DummyHash::class)->findOneBy(['name' => 'Kevin']);
        $this->assertNotNull($kevin);
        $this->assertSame(18, $kevin->age);
    }

    // -------------------------------------------------------------------------
    // bulkUpdate — unique field throws
    // -------------------------------------------------------------------------

    public function testBulkUpdateOnUniqueFieldThrowsBulkOperationException(): void
    {
        $repo = $this->om->getRepository(UniqueHash::class);

        $this->expectException(BulkOperationException::class);
        $repo->bulkUpdate([], ['email' => 'new@example.com']);
    }

    public function testBulkUpdateExceptionMessageContainsFieldName(): void
    {
        $repo = $this->om->getRepository(UniqueHash::class);

        try {
            $repo->bulkUpdate([], ['email' => 'new@example.com']);
            $this->fail('Expected BulkOperationException');
        } catch (BulkOperationException $e) {
            $this->assertStringContainsString('email', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUniqueHash(int $id, string $email, string $name): UniqueHash
    {
        $u = new UniqueHash();
        $u->id = $id;
        $u->email = $email;
        $u->name = $name;

        return $u;
    }

    private function makeCompositeHash(int $id, string $username, int $tenantId, string $email): CompositeUniqueHash
    {
        $u = new CompositeUniqueHash();
        $u->id = $id;
        $u->username = $username;
        $u->tenantId = $tenantId;
        $u->email = $email;

        return $u;
    }
}
