<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;

final class DateTimeConverter extends AbstractDateTimeConverter
{
    public function convert($data): string
    {
        return $data->format(static::FORMAT);
    }

    public function revert($data, string $type): \DateTime
    {
        if (is_array($data)) {
            return new \DateTime(array_shift($data));
        }

        return new \DateTime($data);
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        if ($type === 'DateTime' || $type === 'DateTimeInterface') {
            return true;
        }

        return false;
    }
}
