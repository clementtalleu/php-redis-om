<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractNullConverter implements ConverterInterface
{
    /**
     * @param null $data
     */
    abstract public function convert($data);

    public function revert($data, string $type): null
    {
        return null;
    }

    public function supportsConversion(string $type, mixed $data): bool
    {
        return $type === 'NULL' || $data === null;
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return is_null($value) || $value === 'null';
    }
}
