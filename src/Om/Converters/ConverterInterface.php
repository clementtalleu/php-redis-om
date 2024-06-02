<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

interface ConverterInterface
{
    public function convert($data);

    public function revert($data, string $type);

    public function supportsConversion(string $type, mixed $data): bool;

    public function supportsReversion(string $type, mixed $value): bool;
}
