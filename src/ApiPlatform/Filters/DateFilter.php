<?php

declare(strict_types=1);

namespace Talleu\RedisOm\ApiPlatform\Filters;

use ApiPlatform\Metadata\Parameter;

class DateFilter extends RedisAbstractFilter
{
    private const OPERATOR_MAP = [
        'after'          => '$gte',
        'before'         => '$lte',
        'strictly_after' => '$gt',
        'strictly_before' => '$lt',
    ];

    public function __invoke(array $params, ?Parameter $parameter = null, array $context = []): array
    {
        $value = $parameter->getValue();
        $field = $parameter->getProperty() ?? $parameter->getKey();

        if (!is_array($value)) {
            $ts = strtotime((string) $value);
            if ($ts === false || $ts === 0) {
                return $params;
            }
            $params['criteria'][$field] = ['$gte' => $ts, '$lte' => $ts];

            return $params;
        }

        $range = [];
        foreach ($value as $operator => $operand) {
            if (!isset(self::OPERATOR_MAP[$operator])) {
                continue;
            }
            $ts = strtotime((string) $operand);
            if ($ts === false || $ts === 0) {
                continue;
            }
            $range[self::OPERATOR_MAP[$operator]] = $ts;
        }

        if (!empty($range)) {
            $params['criteria'][$field] = $range;
        }

        return $params;
    }
}
