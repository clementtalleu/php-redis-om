<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\JsonModel;

use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;

final class DateTimeConverter extends AbstractDateTimeConverter
{
    /**
     * @param \DateTime $data
     */
    public function convert($data): array
    {
        $dateArray = (array) $data;
        $dateArray['#type'] = 'DateTime';

        return $dateArray;
    }

    public function revert($data, string $type): \DateTime
    {
        return new \DateTime($data['date'], new \DateTimeZone($data['timezone']));
    }
}
