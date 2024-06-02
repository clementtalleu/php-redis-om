<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

abstract class AbstractDateTimeConverter implements ConverterInterface
{
    public const FORMAT = 'Y-m-d\TH:i:s.uP';

    public const DATETYPES_NAMES = [
        'DateTime',
        'DateTimeInterface',
        'DateTimeImmutable',
    ];

    /**
     * @param \DateTime $data
     */
    abstract public function convert($data);

    public function supportsConversion(string $type, mixed $data): bool
    {
        return ($type === 'DateTime' || $type === 'DateTimeInterface') && $data !== null;
    }


    abstract public function revert($data, string $type): \DateTime;

    abstract public function supportsReversion(string $type, mixed $value): bool;
}
