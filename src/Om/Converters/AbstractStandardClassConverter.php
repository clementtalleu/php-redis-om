<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractStandardClassConverter implements ConverterInterface
{
    abstract public function convert($data);

    public function supportsConversion(string $type, mixed $data): bool
    {
        return $data !== null && $type === 'stdClass';
    }

    abstract public function revert($data, string $type): mixed;

    public function supportsReversion(string $type, mixed $value): bool
    {
        return $type === 'stdClass' && $value !== null;
    }
}
