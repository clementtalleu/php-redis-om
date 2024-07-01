<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

class BooleanConverter implements ConverterInterface
{
    /**
     * @param scalar $data
     */
    public function convert($data)
    {
        if ($data === false) {
            return 'false';
        } elseif ($data === true) {
            return 'true';
        } else {
            return null;
        }
    }

    public function revert($data, string $type)
    {
        if ($data === 'false') {
            return false;
        } elseif ($data === 'true') {
            return true;
        } else {
            return null;
        }
    }

    public function supportsConversion(string $type, mixed $data): bool
    {
        return in_array($type, ['boolean', 'bool']);
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return $type === 'bool';
    }
}
