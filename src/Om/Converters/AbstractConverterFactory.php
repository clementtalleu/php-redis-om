<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractConverterFactory
{
    /** @var array<string, ConverterInterface|null> */
    private static array $converterLookup = [];

    /** @var array<string, ConverterInterface|null> */
    private static array $reverterLookup = [];

    abstract protected static function getConvertersCollection(): array;

    private static function buildCacheKey(string $type, mixed $value): string
    {
        $valuePart = gettype($value);
        if ($value === null || $value === 'null' || $value === 'true' || $value === 'false') {
            $valuePart .= ':' . var_export($value, true);
        } elseif ($value instanceof \BackedEnum) {
            $valuePart = 'enum';
        }

        return static::class . ':' . $type . ':' . $valuePart;
    }

    public static function getConverter($type, $value): ?ConverterInterface
    {
        $cacheKey = self::buildCacheKey($type, $value);

        if (array_key_exists($cacheKey, self::$converterLookup)) {
            return self::$converterLookup[$cacheKey];
        }

        /** @var ConverterInterface $converter */
        foreach (static::getConvertersCollection() as $converter) {
            if ($converter->supportsConversion($type, $value)) {
                self::$converterLookup[$cacheKey] = $converter;
                return $converter;
            }
        }

        self::$converterLookup[$cacheKey] = null;
        return null;
    }

    public static function getReverter(string $type, $value): ?ConverterInterface
    {
        $cacheKey = self::buildCacheKey($type, $value);

        if (array_key_exists($cacheKey, self::$reverterLookup)) {
            return self::$reverterLookup[$cacheKey];
        }

        /** @var ConverterInterface $converter */
        foreach (static::getConvertersCollection() as $converter) {
            if ($converter->supportsReversion($type, $value)) {
                self::$reverterLookup[$cacheKey] = $converter;
                return $converter;
            }
        }

        self::$reverterLookup[$cacheKey] = null;
        return null;
    }

    public static function clearCache(): void
    {
        self::$converterLookup = [];
        self::$reverterLookup = [];
    }
}
