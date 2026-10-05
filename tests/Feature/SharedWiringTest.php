<?php

declare(strict_types=1);

use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\EventPublisher;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Application\Ports\ImageProcessor;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use App\Modules\Shared\Infrastructure\Adapters\InterventionImageProcessor;
use App\Modules\Shared\Infrastructure\Adapters\LaravelEventPublisher;
use App\Modules\Shared\Infrastructure\Adapters\LaravelFileStorage;
use App\Modules\Shared\Infrastructure\Adapters\LaravelTransactionManager;
use App\Modules\Shared\Infrastructure\Adapters\SystemClock;

it('binds every shared port to its adapter', function (string $port, string $adapter) {
    expect(app($port))->toBeInstanceOf($adapter);
})->with([
    'clock' => [Clock::class, SystemClock::class],
    'transactions' => [TransactionManager::class, LaravelTransactionManager::class],
    'events' => [EventPublisher::class, LaravelEventPublisher::class],
    'files' => [FileStorage::class, LaravelFileStorage::class],
    'images' => [ImageProcessor::class, InterventionImageProcessor::class],
]);

it('gives a clock that uses the application time zone', function () {
    expect(app(Clock::class)->now()->getTimezone()->getName())->toBe('Asia/Dhaka');
});

it('lets a test replace the clock', function () {
    app()->instance(Clock::class, FrozenClock::at('2026-10-05 12:00:00'));

    expect(app(Clock::class)->now()->format('Y-m-d H:i'))->toBe('2026-10-05 12:00');
});
