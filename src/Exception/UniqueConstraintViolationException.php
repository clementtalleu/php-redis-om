<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Exception;

final class UniqueConstraintViolationException extends \RuntimeException
{
    public static function forField(string $class, string $field, mixed $value): self
    {
        return self::forFields($class, [$field], [(string) $value]);
    }

    /**
     * @param string[] $fields
     * @param string[] $values  Values in the same order as $fields.
     */
    public static function forFields(string $class, array $fields, array $values): self
    {
        if (count($fields) === 1) {
            return new self(sprintf(
                'Unique constraint violation on %s::%s, value "%s" already exists.',
                $class,
                $fields[0],
                $values[0]
            ));
        }

        $pairs = array_map(fn (string $f, string $v) => sprintf('%s="%s"', $f, $v), $fields, $values);

        return new self(sprintf(
            'Unique constraint violation on %s: combination (%s) already exists.',
            $class,
            implode(', ', $pairs)
        ));
    }

    public static function concurrentModification(): self
    {
        return new self('Transaction aborted: a unique constraint key was modified concurrently. Retry the operation.');
    }
}
