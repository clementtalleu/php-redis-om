<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Exception\BadPropertyConfigurationException;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;

final class AbstractObjectConverterTest extends TestCase
{
    private HashObjectConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new HashObjectConverter();
    }

    public function testExtractPublicProperty(): void
    {
        $object = new AOCTestPublicProps();
        $object->id = 1;
        $object->name = 'visible';

        $result = $this->converter->convert($object);

        $this->assertSame('visible', $result['name']);
    }

    public function testExtractPrivatePropertyWithGetter(): void
    {
        $object = new AOCTestPrivateWithGetter();
        $object->id = 1;
        $object->setSecret('hidden');

        $result = $this->converter->convert($object);

        $this->assertSame('hidden', $result['secret']);
    }

    public function testExtractPrivatePropertyWithCustomGetter(): void
    {
        $object = new AOCTestPrivateWithCustomGetter();
        $object->id = 1;
        $object->setMyValue('custom');

        $result = $this->converter->convert($object);

        $this->assertSame('custom', $result['value']);
    }

    public function testExtractPrivatePropertyWithoutGetterThrows(): void
    {
        $this->expectException(BadPropertyConfigurationException::class);

        $object = new AOCTestPrivateNoGetter();
        $object->id = 1;
        $this->converter->convert($object);
    }

    public function testExtractPrivatePropertyWithInvalidGetterThrows(): void
    {
        $this->expectException(BadPropertyConfigurationException::class);

        $object = new AOCTestPrivateInvalidGetter();
        $object->id = 1;
        $this->converter->convert($object);
    }

    public function testRevertPublicProperty(): void
    {
        $data = ['id' => '1', 'name' => 'restored'];
        $result = $this->converter->revert($data, AOCTestPublicProps::class);

        $this->assertSame('restored', $result->name);
    }

    public function testRevertPrivatePropertyWithSetter(): void
    {
        $data = ['id' => '1', 'secret' => 'restored'];
        $result = $this->converter->revert($data, AOCTestPrivateWithGetter::class);

        $this->assertSame('restored', $result->getSecret());
    }

    public function testRevertPrivatePropertyWithCustomSetter(): void
    {
        $data = ['id' => '1', 'value' => 'custom-restored'];
        $result = $this->converter->revert($data, AOCTestPrivateWithCustomSetter::class);

        $this->assertSame('custom-restored', $result->readValue());
    }

    public function testRevertPrivatePropertyWithoutSetterThrows(): void
    {
        $this->expectException(BadPropertyConfigurationException::class);

        $data = ['id' => '1', 'locked' => 'value'];
        $this->converter->revert($data, AOCTestPrivateNoSetter::class);
    }

    public function testRevertPrivatePropertyWithInvalidSetterThrows(): void
    {
        $this->expectException(BadPropertyConfigurationException::class);

        $data = ['id' => '1', 'value' => 'test'];
        $this->converter->revert($data, AOCTestPrivateInvalidSetter::class);
    }
}

#[RedisOm\Entity]
class AOCTestPublicProps
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';
}

#[RedisOm\Entity]
class AOCTestPrivateWithGetter
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    private string $secret = '';

    public function getSecret(): string { return $this->secret; }
    public function setSecret(string $s): void { $this->secret = $s; }
}

#[RedisOm\Entity]
class AOCTestPrivateWithCustomGetter
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(getter: 'fetchValue')]
    private string $value = '';

    public function fetchValue(): string { return $this->value; }
    public function setMyValue(string $v): void { $this->value = $v; }
}

#[RedisOm\Entity]
class AOCTestPrivateNoGetter
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    private string $locked = 'locked';
}

#[RedisOm\Entity]
class AOCTestPrivateInvalidGetter
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(getter: 'nonExistentMethod')]
    private string $value = 'test';
}

#[RedisOm\Entity]
class AOCTestPrivateWithCustomSetter
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(setter: 'writeValue')]
    private string $value = '';

    public function writeValue(string $v): void { $this->value = $v; }
    public function readValue(): string { return $this->value; }
}

#[RedisOm\Entity]
class AOCTestPrivateNoSetter
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    private string $locked = '';
}

#[RedisOm\Entity]
class AOCTestPrivateInvalidSetter
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property(setter: 'nonExistentSetter')]
    private string $value = '';

    public function getValue(): string { return $this->value; }
}
