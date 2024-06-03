<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister;

enum PersisterOperations: string
{
    case OPERATION_PERSIST = 'doPersist';
    case OPERATION_DELETE = 'doDelete';
    case OPERATION_KEY_NAME = 'operation';
    case PERSISTER_KEY_NAME = 'persister';
}
