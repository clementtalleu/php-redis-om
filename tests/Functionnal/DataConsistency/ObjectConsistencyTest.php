<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\DataConsistency;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\BookHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\DateHash;
use Talleu\RedisOm\Tests\Fixtures\Hash\UserHash;
use Talleu\RedisOm\Tests\Fixtures\Json\BookJson;
use Talleu\RedisOm\Tests\Fixtures\Json\UserJson;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ObjectConsistencyTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    public function testBookHash(): void
    {
        self::emptyRedis();
        self::generateIndex();

        $book = BookHash::create('678CUIO', 'Le talon de fer', 'Une histoire de la révolution');
        $user = UserHash::create('33', 'test@mail.com', 'Toto');
        $book->user = $user;
        $book->tags = [
            $this->createTag('theme', ['politique', 'social', 'histoire']),
            $this->createTag('period', ['XIX', 'XX']),
        ];


        $this->objectManager->persist($book);
        $this->objectManager->flush();

        $retrieveBook = $this->objectManager->getRepository(BookHash::class)->find('678CUIO');
        $this->assertEquals($retrieveBook, $book);
    }

    public function testBookJson(): void
    {
        self::emptyRedis();
        self::generateIndex();

        $book = BookJson::create('678CUIO', 'Le talon de fer', 'Une histoire de la révolution');
        $user = UserJson::create('33', 'test@mail.com', 'Toto');
        $book->user = $user;
        $book->tags = [
            $this->createTag('theme', ['politique', 'social', 'histoire']),
            $this->createTag('period', ['XIX', 'XX']),
        ];



        $this->objectManager->persist($book);
        $this->objectManager->flush();

        $retrieveBook = $this->objectManager->getRepository(BookJson::class)->find('678CUIO');
        $this->assertEquals($retrieveBook, $book);
    }

    private function createTag(string $key, array $values): \stdClass
    {
        $tag = new \stdClass();
        $tag->key = $key;
        $tag->values = $values;

        return $tag;
    }
}
