<?php

declare(strict_types=1);

use App\Modules\Shared\Application\Ports\TransactionManager;
use Illuminate\Support\Facades\DB;

it('returns what the callback returns', function () {
    $result = app(TransactionManager::class)->run(fn () => 42);

    expect($result)->toBe(42);
});

it('keeps the saved rows when the callback finishes', function () {
    app(TransactionManager::class)->run(function () {
        DB::table('cache')->insert(['key' => 'kept', 'value' => 'x', 'expiration' => 0]);
    });

    expect(DB::table('cache')->where('key', 'kept')->exists())->toBeTrue();
});

it('undoes everything when the callback throws', function () {
    try {
        app(TransactionManager::class)->run(function () {
            DB::table('cache')->insert(['key' => 'lost', 'value' => 'x', 'expiration' => 0]);

            throw new RuntimeException('Something broke.');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(DB::table('cache')->where('key', 'lost')->exists())->toBeFalse();
});

it('lets the exception continue upward', function () {
    expect(fn () => app(TransactionManager::class)->run(function () {
        throw new RuntimeException('Something broke.');
    }))->toThrow(RuntimeException::class, 'Something broke.');
});
