<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
use Talleu\RedisOm\Om\Converters\AbstractDateTimeImmutableConverter;

final class DateTimeImmutableConverter extends AbstractDateTimeImmutableConverter
{
    public function convert($data)
    {
        return $data->format(AbstractDateTimeConverter::FORMAT);
    }

    public function revert($data, string $type): \DateTimeImmutable
    {
        if (is_array($data)) {
            return new \DateTimeImmutable(array_shift($data));
        }

        return new \DateTimeImmutable($data);
    }
}
