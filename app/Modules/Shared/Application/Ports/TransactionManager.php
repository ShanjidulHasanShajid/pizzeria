<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Ports;

interface TransactionManager
{
    /**
     * Runs the callback inside one database transaction.
     * If the callback throws, everything it saved is undone and the exception continues upward.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(callable $callback): mixed;
}
