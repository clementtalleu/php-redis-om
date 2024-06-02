<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractObjectConverter implements ConverterInterface
{
    abstract public function convert($data): \stdClass|array;

    public function supportsConversion(string $type, mixed $data): bool
    {
        return $data !== null && class_exists($type) && !in_array($type, AbstractDateTimeConverter::DATETYPES_NAMES);
    }

    abstract public function revert($data, string $type): mixed;

    public function supportsReversion(string $type, mixed $value): bool
    {
        return class_exists($type) && !in_array($type, AbstractDateTimeConverter::DATETYPES_NAMES);
    }
}
