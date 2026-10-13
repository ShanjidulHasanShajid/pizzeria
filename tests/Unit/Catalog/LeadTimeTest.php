<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;

it('labels made-to-order items with minutes and hours', function (): void {
    expect(LeadTime::madeToOrder(25)->label())->toBe('Ready in about 25 min')
        ->and(LeadTime::madeToOrder(120)->label())->toBe('Ready in about 2 h')
        ->and(LeadTime::madeToOrder(90)->label())->toBe('Ready in about 1 h 30 min')
        ->and(LeadTime::madeToOrder(0)->label())->toBe('Ready now');
});

it('labels same-day and next-day items by their class', function (): void {
    expect(LeadTime::sameDay(180)->label())->toBe('Same day')
        ->and(LeadTime::nextDay(1440)->label())->toBe('Next day');
});

it('keeps the class and the minutes', function (): void {
    $leadTime = LeadTime::of(LeadTimeClass::SameDay, 180);

    expect($leadTime->leadTimeClass())->toBe(LeadTimeClass::SameDay)
        ->and($leadTime->minutes())->toBe(180);
});

it('rejects negative minutes', function (): void {
    expect(fn () => LeadTime::madeToOrder(-1))->toThrow(InvalidValue::class);
});

it('rejects more than a week', function (): void {
    expect(fn () => LeadTime::nextDay(LeadTime::MAX_MINUTES + 1))->toThrow(InvalidValue::class);
});

it('compares by value and by length', function (): void {
    expect(LeadTime::sameDay(180)->equals(LeadTime::sameDay(180)))->toBeTrue()
        ->and(LeadTime::sameDay(180)->equals(LeadTime::madeToOrder(180)))->toBeFalse()
        ->and(LeadTime::nextDay(1440)->isLongerThan(LeadTime::madeToOrder(25)))->toBeTrue()
        ->and(LeadTime::madeToOrder(25)->isLongerThan(LeadTime::nextDay(1440)))->toBeFalse();
});
