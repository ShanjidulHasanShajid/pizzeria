<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('runs against the test database, never the development database', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('pizzeria_test');
});
