<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

class EnumConverter implements ConverterInterface
{
    /**
     * @param \BackedEnum $data
     */
    public function convert($data): string|int
    {
        return $data->value;
    }

    /**
     * @param string|int $data
     */
    public function revert($data, string $type): \BackedEnum
    {
        $reflectionEnum = new \ReflectionEnum($type);
        $backingType = $reflectionEnum->getBackingType();

        if ($backingType && $backingType->getName() === 'int' && is_string($data)) {
            $data = (int) $data;
        }

        return $type::from($data);
    }

    public function supportsConversion(string $type, mixed $data): bool
    {
        return $data instanceof \BackedEnum;
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        if ($value === null || $value === 'null') {
            return false;
        }

        return is_subclass_of($type, \BackedEnum::class);
    }
}
