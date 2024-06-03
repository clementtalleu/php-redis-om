<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractConverterFactory;
use Talleu\RedisOm\Om\Converters\ConverterInterface;
use Talleu\RedisOm\Om\Converters\EnumConverter;
use Talleu\RedisOm\Om\Converters\ScalarConverter;

final class ConverterFactory extends AbstractConverterFactory
{
    /**
     * @return ConverterInterface[]
     */
    protected static function getConvertersCollection(): array
    {
        return [
            new HashObjectConverter(),
            new ArrayConverter(),
            new ScalarConverter(),
            new NullConverter(),
            new DateTimeConverter(),
            new DateTimeImmutableConverter(),
            new EnumConverter(),
        ];
    }
}
