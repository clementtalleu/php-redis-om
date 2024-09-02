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
        $dateArray = (array)$data;
        $dateArray['timestamp'] = (string)strtotime($dateArray['date']);
        $dateArray['#type'] = 'DateTime';

        return $dateArray;
    }

    public function revert($data, string $type): \DateTime
    {

        return new \DateTime($data['date'], array_key_exists('timezone', $data) ? new \DateTimeZone($data['timezone']) : null);
    }
}
