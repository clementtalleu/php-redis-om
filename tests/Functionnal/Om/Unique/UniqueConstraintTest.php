<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Unique;

use Talleu\RedisOm\Exception\UniqueConstraintViolationException;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\CompositeUniqueHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\UniqueHash;
use Talleu\RedisOm\Tests\Fixtures\Json\CompositeUniqueJson;
use Talleu\RedisOm\Tests\Fixtures\Json\UniqueJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

final class UniqueConstraintTest extends RedisAbstractTestCase
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
    // Property-level #[Unique] — Hash format
    // -------------------------------------------------------------------------

    public function testPersistDuplicateUniqueFieldHashThrows(): void
    {
        $user1 = $this->makeUniqueHash(1, 'john@example.com', 'John');
        $this->om->persist($user1);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeUniqueHash(2, 'john@example.com', 'Jane');
        $om2->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $om2->flush();
    }

    public function testPersistSameFlushDuplicateUniqueFieldHashThrows(): void
    {
        $user1 = $this->makeUniqueHash(1, 'dup@example.com', 'Alice');
        $user2 = $this->makeUniqueHash(2, 'dup@example.com', 'Bob');

        $this->om->persist($user1);
        $this->om->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->om->flush();
    }

    public function testPersistDistinctUniqueFieldsHashSucceeds(): void
    {
        $user1 = $this->makeUniqueHash(1, 'alice@example.com', 'Alice');
        $user2 = $this->makeUniqueHash(2, 'bob@example.com', 'Bob');

        $this->om->persist($user1);
        $this->om->persist($user2);
        $this->om->flush();

        $this->assertNotNull($this->om->find(UniqueHash::class, 1));
        $this->assertNotNull($this->om->find(UniqueHash::class, 2));
    }

    public function testRepersistSameObjectHashDoesNotThrow(): void
    {
        $user = $this->makeUniqueHash(1, 'same@example.com', 'John');
        $this->om->persist($user);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $om2->persist($user);
        $om2->flush();

        $this->assertNotNull($om2->find(UniqueHash::class, 1));
    }

    public function testRemoveReleasesUniqueKeyHash(): void
    {
        $user = $this->makeUniqueHash(1, 'reclaim@example.com', 'John');
        $this->om->persist($user);
        $this->om->flush();

        $this->om->remove($user);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeUniqueHash(2, 'reclaim@example.com', 'Jane');
        $om2->persist($user2);
        $om2->flush();

        $found = $om2->find(UniqueHash::class, 2);
        $this->assertNotNull($found);
        $this->assertSame('reclaim@example.com', $found->email);
    }

    public function testMergeChangingUniqueFieldHashSucceeds(): void
    {
        $user = $this->makeUniqueHash(1, 'old@example.com', 'John');
        $this->om->persist($user);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $found = $om2->find(UniqueHash::class, 1);
        $found->email = 'new@example.com';
        $om2->merge($found);
        $om2->flush();
        $om2->clear();

        $reloaded = $om2->find(UniqueHash::class, 1);
        $this->assertSame('new@example.com', $reloaded->email);

        // Old email must be released
        $om3 = new RedisObjectManager(self::createRedisClient());
        $other = $this->makeUniqueHash(2, 'old@example.com', 'Jane');
        $om3->persist($other);
        $om3->flush();
        $this->assertNotNull($om3->find(UniqueHash::class, 2));
    }

    public function testMergeDuplicateUniqueFieldHashThrows(): void
    {
        $user1 = $this->makeUniqueHash(1, 'john@example.com', 'John');
        $user2 = $this->makeUniqueHash(2, 'jane@example.com', 'Jane');
        $this->om->persist($user1);
        $this->om->persist($user2);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $found = $om2->find(UniqueHash::class, 2);
        $found->email = 'john@example.com';
        $om2->merge($found);

        $this->expectException(UniqueConstraintViolationException::class);
        $om2->flush();
    }

    // -------------------------------------------------------------------------
    // Property-level #[Unique] — Json format
    // -------------------------------------------------------------------------

    public function testPersistDuplicateUniqueFieldJsonThrows(): void
    {
        $user1 = $this->makeUniqueJson(1, 'john@example.com', 'John');
        $this->om->persist($user1);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeUniqueJson(2, 'john@example.com', 'Jane');
        $om2->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $om2->flush();
    }

    public function testRemoveReleasesUniqueKeyJson(): void
    {
        $user = $this->makeUniqueJson(1, 'reclaim@example.com', 'John');
        $this->om->persist($user);
        $this->om->flush();

        $this->om->remove($user);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeUniqueJson(2, 'reclaim@example.com', 'Jane');
        $om2->persist($user2);
        $om2->flush();

        $found = $om2->find(UniqueJson::class, 2);
        $this->assertNotNull($found);
        $this->assertSame('reclaim@example.com', $found->email);
    }

    // -------------------------------------------------------------------------
    // Class-level #[Unique(properties: [...])] — Hash format
    // -------------------------------------------------------------------------

    public function testPersistDuplicateCompositeUniqueHashThrows(): void
    {
        $user1 = $this->makeCompositeHash(1, 'john', 1, 'john@example.com');
        $this->om->persist($user1);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeCompositeHash(2, 'john', 1, 'other@example.com');
        $om2->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $om2->flush();
    }

    public function testPersistSameFlushDuplicateCompositeUniqueHashThrows(): void
    {
        $user1 = $this->makeCompositeHash(1, 'john', 1, 'a@example.com');
        $user2 = $this->makeCompositeHash(2, 'john', 1, 'b@example.com');

        $this->om->persist($user1);
        $this->om->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->om->flush();
    }

    public function testPersistSameUsernamesDifferentTenantsHashSucceeds(): void
    {
        $user1 = $this->makeCompositeHash(1, 'john', 1, 'john@t1.com');
        $user2 = $this->makeCompositeHash(2, 'john', 2, 'john@t2.com');

        $this->om->persist($user1);
        $this->om->persist($user2);
        $this->om->flush();

        $this->assertNotNull($this->om->find(CompositeUniqueHash::class, 1));
        $this->assertNotNull($this->om->find(CompositeUniqueHash::class, 2));
    }

    public function testRemoveReleasesCompositeUniqueKeyHash(): void
    {
        $user = $this->makeCompositeHash(1, 'john', 1, 'john@example.com');
        $this->om->persist($user);
        $this->om->flush();

        $this->om->remove($user);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeCompositeHash(2, 'john', 1, 'john@example.com');
        $om2->persist($user2);
        $om2->flush();

        $this->assertNotNull($om2->find(CompositeUniqueHash::class, 2));
    }

    // -------------------------------------------------------------------------
    // Class-level #[Unique(properties: [...])] — Json format
    // -------------------------------------------------------------------------

    public function testPersistDuplicateCompositeUniqueJsonThrows(): void
    {
        $user1 = $this->makeCompositeJson(1, 'john', 1, 'john@example.com');
        $this->om->persist($user1);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeCompositeJson(2, 'john', 1, 'other@example.com');
        $om2->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $om2->flush();
    }

    public function testPersistSameUsernamesDifferentTenantsJsonSucceeds(): void
    {
        $user1 = $this->makeCompositeJson(1, 'alice', 10, 'a@t10.com');
        $user2 = $this->makeCompositeJson(2, 'alice', 20, 'a@t20.com');

        $this->om->persist($user1);
        $this->om->persist($user2);
        $this->om->flush();

        $this->assertNotNull($this->om->find(CompositeUniqueJson::class, 1));
        $this->assertNotNull($this->om->find(CompositeUniqueJson::class, 2));
    }

    // -------------------------------------------------------------------------
    // Exception message content
    // -------------------------------------------------------------------------

    public function testExceptionMessageContainsFieldAndValueForPropertyLevel(): void
    {
        $user1 = $this->makeUniqueHash(1, 'taken@example.com', 'John');
        $this->om->persist($user1);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeUniqueHash(2, 'taken@example.com', 'Jane');
        $om2->persist($user2);

        try {
            $om2->flush();
            $this->fail('Expected UniqueConstraintViolationException');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertStringContainsString('email', $e->getMessage());
            $this->assertStringContainsString('taken@example.com', $e->getMessage());
        }
    }

    public function testExceptionMessageContainsBothFieldsForComposite(): void
    {
        $user1 = $this->makeCompositeHash(1, 'bob', 5, 'bob@example.com');
        $this->om->persist($user1);
        $this->om->flush();

        $om2 = new RedisObjectManager(self::createRedisClient());
        $user2 = $this->makeCompositeHash(2, 'bob', 5, 'other@example.com');
        $om2->persist($user2);

        try {
            $om2->flush();
            $this->fail('Expected UniqueConstraintViolationException');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertStringContainsString('username', $e->getMessage());
            $this->assertStringContainsString('tenantId', $e->getMessage());
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

    private function makeUniqueJson(int $id, string $email, string $name): UniqueJson
    {
        $u = new UniqueJson();
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

    private function makeCompositeJson(int $id, string $username, int $tenantId, string $email): CompositeUniqueJson
    {
        $u = new CompositeUniqueJson();
        $u->id = $id;
        $u->username = $username;
        $u->tenantId = $tenantId;
        $u->email = $email;
        return $u;
    }
}
