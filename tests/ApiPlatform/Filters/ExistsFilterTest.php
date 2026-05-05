<?php

declare(strict_types=1);

namespace ApiPlatform\Filters;

use PHPUnit\Framework\Attributes\DataProvider;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\ApiPlatform\Entity\Dummy;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class ExistsFilterTest extends RedisAbstractTestCase
{
    private RedisObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new RedisObjectManager(RedisAbstractTestCase::createRedisClient());
        parent::setUp();
    }

    private function loadFixtures(): void
    {
        self::emptyRedis();
        self::generateIndex();
        $dummies = self::loadRedisFixtures(Dummy::class);

        // dummy1 and dummy2 get a description, dummy3 stays null
        $dummies[0]->description = 'Has a description';
        $dummies[1]->description = 'Also has one';
        $this->objectManager->persist($dummies[0]);
        $this->objectManager->persist($dummies[1]);
        $this->objectManager->flush();
    }

    #[DataProvider('provideTrueValues')]
    public function testExistsTrue(string $value): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', "/api/dummies?exists[description]=$value");
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(2, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertNotNull($result['description']);
        }
    }

    public static function provideTrueValues(): array
    {
        return [
            'string "true"' => ['true'],
            'numeric string "1"' => ['1'],
        ];
    }

    #[DataProvider('provideFalseValues')]
    public function testExistsFalse(string $value): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', "/api/dummies?exists[description]=$value");
        $this->assertEquals(200, $response->getStatusCode());
        $responseContent = $response->toArray();
        $this->assertEquals(1, $responseContent['totalItems']);
        foreach ($responseContent['member'] as $result) {
            $this->assertTrue(!isset($result['description']) || $result['description'] === null);
        }
    }

    public static function provideFalseValues(): array
    {
        return [
            'string "false"' => ['false'],
            'numeric string "0"' => ['0'],
        ];
    }

    public function testExistsInvalidIgnored(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/api/dummies?exists[description]=foo');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $response->toArray()['totalItems']);
    }
}
