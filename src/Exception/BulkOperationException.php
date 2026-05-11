<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Exception;

final class BulkOperationException extends \RuntimeException
{
    /**
     * @param string[] $fields
     */
    public static function uniqueFieldsCannotBeBulkUpdated(string $className, array $fields): self
    {
        return new self(sprintf(
            'Cannot bulk-update unique fields [%s] on %s. Use stream() + merge() + flush() instead.',
            implode(', ', $fields),
            $className,
        ));
    }
}
