<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Metadata;

use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class MetadataTest extends RedisAbstractTestCase
{
    public function testGetClassMetadataHasAssociation()
    {
        $objectManager = new RedisObjectManager();
        $classMetadata = $objectManager->getClassMetadata(DummyHash::class);

        $this->assertEquals(['id'], $classMetadata->getIdentifier());
        $this->assertCount(11, $classMetadata->fieldsMapping);
        $this->assertCount(11, $classMetadata->typesFields);
    }
}
