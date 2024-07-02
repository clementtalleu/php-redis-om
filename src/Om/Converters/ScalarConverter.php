<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

class ScalarConverter implements ConverterInterface
{
    /**
     * @param scalar $data
     */
    public function convert($data)
    {
        return (string) $data;
    }

    public function revert($data, string $type)
    {
        if ($type === 'int') {
            return (int) $data;
        } elseif ($type === 'float') {
            return (float) $data;
        }

        return (string) $data;
    }

    public function supportsConversion(string $type, mixed $data): bool
    {
        return in_array($type, ['int', 'string', 'double', 'integer', 'float', 'null']) && $data !== null;
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return in_array($type, ['int', 'string', 'float']) && $value !== null && $value !== 'null';
    }
}
