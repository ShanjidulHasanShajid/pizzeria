<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use App\Modules\Shared\Application\Ports\TransactionManager;

/**
 * "Transaction" for tests: just runs the callback.
 */
final class PassThroughTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return $callback();
    }
}
