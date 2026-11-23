<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Domain\Exceptions\InvalidOptionGroup;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

function savedCrustOption(int $id, string $name, int $sort): Option
{
    return Option::reconstitute($id, $name, null, false, true, SortOrder::fromInt($sort), PriceMode::Flat, Money::zero(), []);
}

it('creates an active group without options, with cleaned texts', function (): void {
    $group = OptionGroup::create(' Pizza crusts ', ' Choose your crust ', SortOrder::first(), description: '  ');

    expect($group->id())->toBeNull()
        ->and($group->name())->toBe('Pizza crusts')
        ->and($group->displayTitle())->toBe('Choose your crust')
        ->and($group->description())->toBeNull()
        ->and($group->isActive())->toBeTrue()
        ->and($group->options())->toBe([]);
});

it('refuses a second option with the same name, whatever the case', function (): void {
    $group = OptionGroup::create('Pizza crusts', 'Crust', SortOrder::first());
    $group->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::first()));

    $group->addOption(Option::withFlatPrice(' thin ', Money::zero(), SortOrder::fromInt(20)));
})->throws(InvalidOptionGroup::class, 'already an option called');

it('refuses saved options with the same name', function (): void {
    OptionGroup::reconstitute(1, 'Pizza crusts', 'Crust', null, true, SortOrder::first(), [
        savedCrustOption(11, 'Thin', 10),
        savedCrustOption(12, 'THIN', 20),
    ]);
})->throws(InvalidOptionGroup::class);

it('renames an option, but not to the name of another option', function (): void {
    $group = OptionGroup::reconstitute(1, 'Pizza crusts', 'Crust', null, true, SortOrder::first(), [
        savedCrustOption(11, 'Regular', 10),
        savedCrustOption(12, 'Thin', 20),
    ]);

    $group->renameOption(12, 'Thin & crispy');
    $group->renameOption(11, 'regular'); // its own name in another case is fine

    expect($group->option(12)?->name())->toBe('Thin & crispy')
        ->and($group->option(11)?->name())->toBe('regular');
    expect(fn () => $group->renameOption(11, 'Thin & Crispy'))->toThrow(InvalidOptionGroup::class);
    expect(fn () => $group->renameOption(99, 'Stuffed'))->toThrow(InvalidOptionGroup::class);
});

it('lists the options in sort order and finds one by id', function (): void {
    $group = OptionGroup::reconstitute(1, 'Pizza crusts', 'Crust', null, true, SortOrder::first(), [
        savedCrustOption(12, 'Thin', 20),
        savedCrustOption(11, 'Regular', 10),
    ]);

    expect(array_map(fn (Option $option): string => $option->name(), $group->options()))->toBe(['Regular', 'Thin'])
        ->and($group->option(12)?->name())->toBe('Thin')
        ->and($group->option(99))->toBeNull();
});

it('removes an option and gives the next position after the last one', function (): void {
    $group = OptionGroup::reconstitute(1, 'Pizza crusts', 'Crust', null, true, SortOrder::first(), [
        savedCrustOption(11, 'Regular', 10),
        savedCrustOption(12, 'Thin', 20),
    ]);

    expect($group->nextOptionSort()->value())->toBe(30);

    $group->removeOption(12);

    expect($group->option(12))->toBeNull()
        ->and($group->nextOptionSort()->value())->toBe(20)
        ->and(OptionGroup::create('New', 'New', SortOrder::first())->nextOptionSort()->value())->toBe(10);
    expect(fn () => $group->removeOption(12))->toThrow(InvalidOptionGroup::class);
});

it('puts its options in a new order', function (): void {
    $group = OptionGroup::reconstitute(1, 'Pizza crusts', 'Crust', null, true, SortOrder::first(), [
        savedCrustOption(11, 'Regular', 10),
        savedCrustOption(12, 'Thin', 20),
    ]);

    $group->reorderOptions([12, 11]);

    expect(array_map(fn (Option $option): string => $option->name(), $group->options()))->toBe(['Thin', 'Regular']);
});

it('refuses an order that does not name every option once', function (array $ids): void {
    $group = OptionGroup::reconstitute(1, 'Pizza crusts', 'Crust', null, true, SortOrder::first(), [
        savedCrustOption(11, 'Regular', 10),
        savedCrustOption(12, 'Thin', 20),
    ]);

    $group->reorderOptions($ids);
})->throws(InvalidOptionGroup::class)->with([
    'one missing' => [[11]],
    'one twice' => [[11, 11]],
    'one unknown' => [[11, 99]],
]);
