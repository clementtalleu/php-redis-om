<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractArrayConverter implements ConverterInterface
{
    /**
     * @param array $data
     */
    abstract public function convert($data);

    public function supportsConversion(string $type, mixed $data): bool
    {
        return ($type === 'array' || $type === 'iterable' || is_iterable($data)) && $data !== null;
    }

    abstract public function revert($data, string $type): mixed;

    public function supportsReversion(string $type, mixed $value): bool
    {
        return ($type === 'array' || $type === 'iterable') && $value !== null;
    }
}
