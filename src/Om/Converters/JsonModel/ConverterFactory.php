<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\JsonModel;

use Talleu\RedisOm\Om\Converters\AbstractConverterFactory;
use Talleu\RedisOm\Om\Converters\BooleanConverter;
use Talleu\RedisOm\Om\Converters\ConverterInterface;
use Talleu\RedisOm\Om\Converters\ScalarConverter;

final class ConverterFactory extends AbstractConverterFactory
{
    /**
     * @return ConverterInterface[]
     */
    protected static function getConvertersCollection(): array
    {
        return [
            new JsonObjectConverter(),
            new ArrayConverter(),
            new ScalarConverter(),
            new BooleanConverter(),
            new StandardClassConverter(),
            new NullConverter(),
            new DateTimeConverter(),
            new DateTimeImmutableConverter(),
        ];
    }
}
