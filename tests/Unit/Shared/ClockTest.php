<?php

declare(strict_types=1);

use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use App\Modules\Shared\Infrastructure\Adapters\SystemClock;

it('stays at the moment it was frozen', function () {
    $clock = FrozenClock::at('2026-10-05 12:00:00');

    expect($clock->now()->format('Y-m-d H:i:s'))->toBe('2026-10-05 12:00:00')
        ->and($clock->now()->getTimezone()->getName())->toBe('Asia/Dhaka');
});

it('moves forward only when told to', function () {
    $clock = FrozenClock::at('2026-10-05 12:00:00');
    $clock->advance('+1 day');

    expect($clock->now()->format('Y-m-d H:i:s'))->toBe('2026-10-06 12:00:00');
});

it('reports the real time in the requested time zone', function () {
    $clock = new SystemClock('Asia/Dhaka');

    expect($clock->now()->getTimezone()->getName())->toBe('Asia/Dhaka');
});
