<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class RangeFilter extends RedisAbstractFilter
{
    private const OPERATOR_MAP = [
        'gt'  => '$gt',
        'gte' => '$gte',
        'lt'  => '$lt',
        'lte' => '$lte',
    ];

    public function __invoke(array $params, ?Parameter $parameter = null, array $context = []): array
    {
        $value = $parameter->getValue();
        if (!is_array($value)) {
            return $params;
        }

        $field = $parameter->getProperty() ?? $parameter->getKey();
        $range = [];

        foreach ($value as $operator => $operand) {
            if ($operator === 'between') {
                $parts = explode('..', (string) $operand, 2);
                if (isset($parts[0], $parts[1]) && is_numeric($parts[0]) && is_numeric($parts[1])) {
                    $range['$gte'] = (float) $parts[0];
                    $range['$lte'] = (float) $parts[1];
                }
                continue;
            }

            if (isset(self::OPERATOR_MAP[$operator]) && is_numeric($operand)) {
                $range[self::OPERATOR_MAP[$operator]] = (float) $operand;
            }
        }

        if (!empty($range)) {
            $params['criteria'][$field] = $range;
        }

        return $params;
    }
}
