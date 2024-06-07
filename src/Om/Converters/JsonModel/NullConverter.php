<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\JsonModel;

use Talleu\RedisOm\Om\Converters\AbstractNullConverter;

final class NullConverter extends AbstractNullConverter
{
    /**
     * @param null $data
     */
    public function convert($data): null
    {
        return null;
    }
}
