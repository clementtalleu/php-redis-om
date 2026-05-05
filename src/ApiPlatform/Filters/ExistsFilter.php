<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class ExistsFilter extends RedisAbstractFilter
{
    public function __construct(private readonly array $properties = [])
    {
    }

    public function __invoke(array $params, ?Parameter $parameter = null, array $context = []): array
    {
        $property = $parameter->getProperty();
        $value = $parameter->getValue();

        // API Platform 4.1: value is ['fieldName' => 'true/false']
        if (is_array($value)) {
            foreach ($value as $field => $raw) {
                if ($this->properties !== [] && !in_array($field, $this->properties, true)) {
                    continue;
                }
                $normalized = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($normalized === null) {
                    continue;
                }
                $params['additionalRangeFilters'][$field . '_exists'] = $normalized
                    ? sprintf('-@%s:{null}', $field)
                    : sprintf('@%s:{null}', $field);
            }

            return $params;
        }

        // API Platform 4.2+: property is fieldName, value is 'true/false'
        if ($this->properties !== [] && !in_array($property, $this->properties, true)) {
            return $params;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalized === null) {
            return $params;
        }

        $params['additionalRangeFilters'][$property . '_exists'] = $normalized
            ? sprintf('-@%s:{null}', $property)
            : sprintf('@%s:{null}', $property);

        return $params;
    }
}
