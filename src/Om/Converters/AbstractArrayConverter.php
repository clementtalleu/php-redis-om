<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

use Talleu\RedisOm\Om\Converters\ConverterInterface;

abstract class AbstractArrayConverter implements ConverterInterface
{
    /**
     * @param array $data
     */
    abstract public function convert($data);

    public function supportsConversion(string $type, mixed $data): bool
    {
        return ($type === 'array' || $type === 'iterable') && $data !== null;
    }

    abstract public function revert($data, string $type): mixed;

    public function supportsReversion(string $type, mixed $value): bool
    {
        // if (is_array($value) && array_key_exists('date', $value) &&  array_key_exists('timezone', $value)) {
        //     return false;
        // }

        return $type === 'array' || $type === 'iterable' && $value !== null;
    }
}
