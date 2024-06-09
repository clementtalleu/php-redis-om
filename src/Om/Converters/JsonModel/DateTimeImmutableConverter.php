<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\JsonModel;

use Talleu\RedisOm\Om\Converters\AbstractDateTimeImmutableConverter;

final class DateTimeImmutableConverter extends AbstractDateTimeImmutableConverter
{
    /**
     * @param \DateTimeImmutable $data
     */
    public function convert($data): array
    {
        $dateArray = (array) $data;
        $dateArray['#type'] = 'DateTimeImmutable';

        return $dateArray;
    }

    public function revert($data, string $type): \DateTimeImmutable
    {
        return new \DateTimeImmutable($data['date'], array_key_exists('timezone', $data) ? new \DateTimeZone($data['timezone']) : null);
    }
}
