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
        return new \DateTimeImmutable($data['date'], new \DateTimeZone($data['timezone']));
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return $type === 'DateTimeImmutable';
    }
}
