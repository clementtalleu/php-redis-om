<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

class EnumConverter implements ConverterInterface
{
    /**
     * @param \UnitEnum $data
     */
    public function convert($data): string
    {
        return (string) $data->value;
    }

    /**
     * @return array
     */
    public function revert($data, mixed $object): mixed
    {
        // TODO: Implement revert() method.
    }

    public function supportsConversion(string $type, mixed $data): bool
    {
        return $data instanceof \UnitEnum;
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return false;
    }
}
