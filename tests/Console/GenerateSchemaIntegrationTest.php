<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Console;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Command\GenerateSchema;
use Talleu\RedisOm\Exception\BadIdentifierConfigurationException;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Client\Client;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;

class GenerateSchemaIntegrationTest extends TestCase
{
    private RedisClientInterface $redisClient;

    protected function setUp(): void
    {
        $this->redisClient = (new Client())->redisClient;
        $this->redisClient->flushAll();
        GenerateSchema::generateSchema(__DIR__ . '/../Fixtures', $this->redisClient);
    }

    public function testIdTagIndexWorksAfterGenerateSchema(): void
    {
        $om = new RedisObjectManager($this->redisClient);
        $dummy = DummyHash::create(id: 99, age: 30, price: 9.99, name: 'TagTest');
        $om->persist($dummy);
        $om->flush();

        $count = $this->redisClient->count(DummyHash::class, ['id' => '99']);
        $this->assertSame(1, $count, 'TAG index on id is not searchable');
    }

    public function testIdNumericIndexWorksAfterGenerateSchema(): void
    {
        $om = new RedisObjectManager($this->redisClient);
        $dummy = DummyHash::create(id: 99, age: 30, price: 9.99, name: 'NumericTest');
        $om->persist($dummy);
        $om->flush();

        // Validates the NUMERIC alias id_numeric is present in the index
        $count = $this->redisClient->count(DummyHash::class, [], null, ['@id_numeric:[99 99]']);
        $this->assertSame(1, $count, 'NUMERIC index on id is not searchable');
    }

    public function testFindByIdWorksAfterGenerateSchema(): void
    {
        $om = new RedisObjectManager($this->redisClient);
        $dummy = DummyHash::create(id: 42, age: 30, price: 9.99, name: 'FindById');
        $om->persist($dummy);
        $om->flush();

        $repo = $om->getRepository(DummyHash::class);
        $results = $repo->findBy(['id' => 42]);

        $this->assertCount(1, $results);
        $this->assertSame(42, $results[0]->id);
        $this->assertSame('FindById', $results[0]->name);
    }

    public function testUnsupportedIdTypeThrowsBadIdentifierConfigurationException(): void
    {
        $dir = sys_get_temp_dir() . '/redis-om-bad-id-' . getmypid();
        @mkdir($dir);

        $src = <<<'PHP'
<?php
namespace RedisOmTest\BadId;
use Talleu\RedisOm\Om\Mapping as RedisOm;
#[RedisOm\Entity]
class DummyWithArrayId {
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?array $id = null;
    #[RedisOm\Property(index: true)]
    public string $name = 'test';
}
PHP;
        file_put_contents($dir . '/DummyWithArrayId.php', $src);
        require_once $dir . '/DummyWithArrayId.php';

        try {
            GenerateSchema::generateSchema($dir, $this->redisClient);
            $this->fail('Expected BadIdentifierConfigurationException was not thrown');
        } catch (BadIdentifierConfigurationException $e) {
            $this->assertMatchesRegularExpression("/Identifier 'id'.*unsupported type 'array'/", $e->getMessage());
        } finally {
            @unlink($dir . '/DummyWithArrayId.php');
            @rmdir($dir);
        }
    }
}
