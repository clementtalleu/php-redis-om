<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractDateTimeImmutableConverter implements ConverterInterface
{
    public function supportsConversion(string $type, mixed $data): bool
    {
        return $type === 'DateTimeImmutable' && $data !== null;
    }

    abstract public function revert($data, string $type): \DateTimeImmutable;

    public function supportsReversion(string $type, mixed $value): bool
    {
        return $type === 'DateTimeImmutable';
    }
}
