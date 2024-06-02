<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractNullConverter;

final class NullConverter extends AbstractNullConverter
{
    /**
     * @param null $data
     */
    public function convert($data): string
    {
        return 'null';
    }

    public function revert($data, string $type): mixed
    {
        return null;
    }
}
