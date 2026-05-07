<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Exception\UniqueConstraintViolationException;

final class UniqueConstraintViolationExceptionTest extends TestCase
{
    public function testForFieldSingleFieldMessage(): void
    {
        $e = UniqueConstraintViolationException::forField('App\\Entity\\User', 'email', 'john@example.com');

        $this->assertStringContainsString('App\\Entity\\User::email', $e->getMessage());
        $this->assertStringContainsString('"john@example.com"', $e->getMessage());
    }

    public function testForFieldDelegatesToForFields(): void
    {
        $viaField  = UniqueConstraintViolationException::forField('Foo', 'bar', 'baz');
        $viaFields = UniqueConstraintViolationException::forFields('Foo', ['bar'], ['baz']);

        $this->assertSame($viaField->getMessage(), $viaFields->getMessage());
    }

    public function testForFieldsSingleElementUsesPropertyFormat(): void
    {
        $e = UniqueConstraintViolationException::forFields('My\\Entity', ['slug'], ['hello-world']);

        $this->assertStringContainsString('My\\Entity::slug', $e->getMessage());
        $this->assertStringContainsString('"hello-world"', $e->getMessage());
    }

    public function testForFieldsCompositeMessage(): void
    {
        $e = UniqueConstraintViolationException::forFields(
            'App\\Entity\\User',
            ['tenantId', 'username'],
            ['42', 'john']
        );

        $this->assertStringContainsString('App\\Entity\\User', $e->getMessage());
        $this->assertStringContainsString('tenantId="42"', $e->getMessage());
        $this->assertStringContainsString('username="john"', $e->getMessage());
        $this->assertStringNotContainsString('::', $e->getMessage());
    }

    public function testConcurrentModificationMessage(): void
    {
        $e = UniqueConstraintViolationException::concurrentModification();

        $this->assertStringContainsString('concurrent', strtolower($e->getMessage()));
    }

    public function testAllFactoryMethodsReturnCorrectType(): void
    {
        $this->assertInstanceOf(UniqueConstraintViolationException::class, UniqueConstraintViolationException::forField('C', 'f', 'v'));
        $this->assertInstanceOf(UniqueConstraintViolationException::class, UniqueConstraintViolationException::forFields('C', ['f'], ['v']));
        $this->assertInstanceOf(UniqueConstraintViolationException::class, UniqueConstraintViolationException::concurrentModification());
    }
}
