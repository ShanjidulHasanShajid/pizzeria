<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\ValueObjects\LineSelection;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

it('keeps the chosen ids per group, sorted, and forgets empty groups', function (): void {
    $selection = LineSelection::of(10, 103, [2 => [24, 21], 1 => [13], 3 => []], Quantity::of(2));

    expect($selection->productId())->toBe(10)
        ->and($selection->variantId())->toBe(103)
        ->and($selection->optionGroupIds())->toBe([1, 2])
        ->and($selection->optionIdsFor(2))->toBe([21, 24])
        ->and($selection->optionIdsFor(3))->toBe([])
        ->and($selection->quantity()->value())->toBe(2);
});

it('refuses the same option twice and ids that cannot exist', function (array $choices): void {
    LineSelection::of(10, 103, $choices, Quantity::of(1));
})->throws(InvalidValue::class)->with([
    'same option twice' => [[2 => [21, 21]]],
    'group id 0' => [[0 => [21]]],
    'option id 0' => [[2 => [0]]],
]);

it('sees the same choice whatever the click order or the quantity', function (): void {
    $first = LineSelection::of(10, 103, [1 => [13], 2 => [21, 24]], Quantity::of(1));
    $second = LineSelection::of(10, 103, [2 => [24, 21], 1 => [13]], Quantity::of(3));
    $otherSize = LineSelection::of(10, 102, [1 => [13], 2 => [21, 24]], Quantity::of(1));

    expect($first->isSameChoiceAs($second))->toBeTrue()
        ->and($first->isSameChoiceAs($otherSize))->toBeFalse();
});
