<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Adapters;

use App\Modules\Shared\Application\Ports\TransactionManager;
use Illuminate\Database\DatabaseManager;

final class LaravelTransactionManager implements TransactionManager
{
    public function __construct(private readonly DatabaseManager $database) {}

    public function run(callable $callback): mixed
    {
        return $this->database->transaction(fn (): mixed => $callback());
    }
}
