<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;

it('keeps valid rules and offers two shortcuts', function (): void {
    $rules = SelectionRules::of(isRequired: false, minimum: 0, maximum: 5, freeCount: 2);

    expect($rules->isRequired())->toBeFalse()
        ->and($rules->minimum())->toBe(0)
        ->and($rules->maximum())->toBe(5)
        ->and($rules->freeCount())->toBe(2)
        ->and(SelectionRules::exactlyOne()->equals(SelectionRules::of(true, 1, 1)))->toBeTrue()
        ->and(SelectionRules::upTo(3, freeCount: 1)->equals(SelectionRules::of(false, 0, 3, 1)))->toBeTrue();
});

it('refuses rules that cannot work', function (bool $isRequired, int $minimum, int $maximum, int $freeCount): void {
    SelectionRules::of($isRequired, $minimum, $maximum, $freeCount);
})->throws(InvalidValue::class)->with([
    'maximum below 1' => [false, 0, 0, 0],
    'maximum above 30' => [false, 0, 31, 0],
    'negative minimum' => [false, -1, 3, 0],
    'minimum above maximum' => [false, 4, 3, 0],
    'required without a minimum' => [true, 0, 3, 0],
    'more free picks than the maximum' => [false, 0, 2, 3],
]);
