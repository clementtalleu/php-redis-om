<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractConverterFactory
{
    abstract protected static function getConvertersCollection(): array;

    public static function getConverter($type, $value): ?ConverterInterface
    {
        /** @var ConverterInterface $converter */
        foreach (static::getConvertersCollection() as $converter) {
            if ($converter->supportsConversion($type, $value)) {
                return $converter;
            }
        }

        return null;
    }

    public static function getReverter(string $type, $value): ?ConverterInterface
    {
        /** @var ConverterInterface $converter */
        foreach (static::getConvertersCollection() as $converter) {
            if ($converter->supportsReversion($type, $value)) {
                return $converter;
            }
        }

        return null;
    }
}
