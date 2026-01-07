<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Mapping;

use Attribute;

/**
 * This attribute should be used to persist the properties of an entity in the Redis datastore.
 * You could leave the index true to benefits of automatic indexing,
 * #[Property(index: true)]  will enable an index for this property, by default it will be a text + tag index
 * Or you couldd specify the indexe(s) type(s) for each property you want to query :
 * #[Property(type: ['title' => 'TEXT', 'title_tag' => 'TAG'])]
 * #[Property(type: ['price' => 'NUMERIC', 'price_tag' => 'TAG'])]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Property
{
    public const INDEX_TEXT = 'TEXT';
    public const INDEX_TAG = 'TAG';
    public const INDEX_NUMERIC = 'NUMERIC';
    public const INDEX_GEO = 'GEO';
    public const INDEX_VECTOR = 'VECTOR';
    public const INDEX_GEOSHAPE = 'GEOSHAPE';

    public const INDEX_TYPES = [
        self::INDEX_TEXT,
        self::INDEX_TAG,
        self::INDEX_NUMERIC,
        self::INDEX_GEO,
        self::INDEX_VECTOR,
        self::INDEX_GEOSHAPE,
    ];

    /**
     * @param array|string|null $index could be an array of index types with name or a single index type
     * @param string|null $getter
     * @param string|null $setter
     */
    public function __construct(
        public null|bool|array|string $index = false,
        public ?string                $getter = null,
        public ?string                $setter = null,
    ) {
        if (is_bool($index)) {
            return;
        }

        if (is_string($index)) {
            $this->index = [$index];
        }

        foreach ($this->index as $indexType) {
            if (!in_array($indexType, self::INDEX_TYPES)) {
                throw new \InvalidArgumentException("Index type $indexType is not supported, should be one of: " . implode(', ', self::INDEX_TYPES));
            }
        }
    }
}
