<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Unique
{
    /**
     * @param string[] $properties Field names for a composite unique constraint (class-level only).
     *                             Leave empty when placed on a property.
     */
    public function __construct(
        public readonly array $properties = [],
    ) {
    }
}
