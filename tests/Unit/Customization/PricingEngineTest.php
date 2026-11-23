<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Domain\Enums\PricingFailureReason;
use App\Modules\Customization\Domain\Exceptions\PricingFailed;
use App\Modules\Customization\Domain\Services\PricingEngine;
use App\Modules\Customization\Domain\ValueObjects\LineSelection;
use App\Modules\Customization\Domain\ValueObjects\PricedLine;
use App\Modules\Customization\Domain\ValueObjects\PricedOption;
use App\Modules\Customization\Domain\ValueObjects\PricingProduct;
use App\Modules\Customization\Domain\ValueObjects\PricingVariant;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/*
| The menu used by every test below (size tiers: 1 Personal, 2 Medium, 3 Large).
| Margherita (product 10): Personal ৳450, Medium ৳850, Large ৳1250, and a Slice ৳150 without a size.
| Group 1 "Crust" (required, exactly 1): Regular ৳0, Thin ৳0, Stuffed ৳100/150/200,
|   Cheese-filled only Medium ৳180 / Large ৳250, Gluten-free ৳120 (sold out).
| Group 2 "Extra toppings" (optional, up to 3 unless a test says otherwise):
|   Olives ৳60, Mushrooms ৳80, Pepperoni ৳100/150/200, Jalapeños ৳50, Corn ৳50, Basil ৳0.
| Group 3 "Dips": Garlic dip ৳40. It is NOT attached to the Margherita.
*/

function pricingMargherita(bool $isPublished = true, bool $isAvailable = true, bool $largeAvailable = true): PricingProduct
{
    return new PricingProduct(10, 'Margherita', $isPublished, $isAvailable, [
        new PricingVariant(101, 'Personal', Money::fromTaka('450'), 1, true),
        new PricingVariant(102, 'Medium', Money::fromTaka('850'), 2, true),
        new PricingVariant(103, 'Large', Money::fromTaka('1250'), 3, $largeAvailable),
        new PricingVariant(104, 'Slice', Money::fromTaka('150'), null, true),
    ]);
}

/**
 * @param  string|array<int, string>  $price  taka for a flat price, or size tier id => taka
 */
function pricingOption(int $id, string $name, int $sort, string|array $price, bool $isAvailable = true): Option
{
    if (is_array($price)) {
        $prices = array_map(fn (string $taka): Money => Money::fromTaka($taka), $price);

        return Option::reconstitute($id, $name, null, false, $isAvailable, SortOrder::fromInt($sort), PriceMode::PerSize, null, $prices);
    }

    return Option::reconstitute($id, $name, null, false, $isAvailable, SortOrder::fromInt($sort), PriceMode::Flat, Money::fromTaka($price), []);
}

/**
 * @return list<OptionGroup>
 */
function pricingGroups(bool $crustActive = true): array
{
    return [
        OptionGroup::reconstitute(1, 'Pizza crusts', 'Crust', null, $crustActive, SortOrder::fromInt(10), [
            pricingOption(11, 'Regular', 10, '0'),
            pricingOption(12, 'Thin', 20, '0'),
            pricingOption(13, 'Stuffed', 30, [1 => '100', 2 => '150', 3 => '200']),
            pricingOption(14, 'Cheese-filled', 40, [2 => '180', 3 => '250']),
            pricingOption(15, 'Gluten-free', 50, '120', isAvailable: false),
        ]),
        OptionGroup::reconstitute(2, 'Pizza extra toppings', 'Extra toppings', null, true, SortOrder::fromInt(20), [
            pricingOption(21, 'Olives', 10, '60'),
            pricingOption(22, 'Mushrooms', 20, '80'),
            pricingOption(23, 'Pepperoni', 30, [1 => '100', 2 => '150', 3 => '200']),
            pricingOption(24, 'Jalapeños', 40, '50'),
            pricingOption(25, 'Corn', 50, '50'),
            pricingOption(26, 'Basil', 60, '0'),
        ]),
        OptionGroup::reconstitute(3, 'Dips', 'Dips', null, true, SortOrder::fromInt(30), [
            pricingOption(31, 'Garlic dip', 10, '40'),
        ]),
    ];
}

/**
 * @return list<ProductOptionGroup>
 */
function pricingAttachments(?SelectionRules $toppings = null): array
{
    return [
        ProductOptionGroup::reconstitute(1, 10, 1, SelectionRules::exactlyOne(), SortOrder::fromInt(10)),
        ProductOptionGroup::reconstitute(2, 10, 2, $toppings ?? SelectionRules::upTo(3), SortOrder::fromInt(20)),
    ];
}

/**
 * Prices one Margherita line with the menu above.
 *
 * @param  array<int, list<int>>  $choices  option group id => option ids
 */
function pricingQuote(
    int $variantId,
    array $choices,
    int $quantity = 1,
    ?SelectionRules $toppings = null,
    ?PricingProduct $product = null,
    bool $crustActive = true,
): PricedLine {
    return (new PricingEngine)->price(
        $product ?? pricingMargherita(),
        LineSelection::of(10, $variantId, $choices, Quantity::of($quantity)),
        pricingAttachments($toppings),
        pricingGroups($crustActive),
    );
}

/** Runs the pricing and returns why it failed. */
function pricingFailure(Closure $price): PricingFailureReason
{
    try {
        $price();
    } catch (PricingFailed $failed) {
        return $failed->reason;
    }

    throw new RuntimeException('Expected the pricing to fail, but it worked.');
}

describe('prices', function (): void {
    it('uses the price of the chosen size as the base price', function (): void {
        expect(pricingQuote(101, [1 => [11]])->unitPrice()->poisha())->toBe(45_000)
            ->and(pricingQuote(103, [1 => [11]])->unitPrice()->poisha())->toBe(125_000);
    });

    it('adds a flat-priced option', function (): void {
        expect(pricingQuote(102, [1 => [11], 2 => [21]])->unitPrice()->poisha())->toBe(91_000);
    });

    it('adds a per-size option at the price of the chosen size', function (int $variantId, int $expected): void {
        expect(pricingQuote($variantId, [1 => [13]])->unitPrice()->poisha())->toBe($expected);
    })->with([
        'Personal: 450 + 100' => [101, 55_000],
        'Medium: 850 + 150' => [102, 100_000],
        'Large: 1250 + 200' => [103, 145_000],
    ]);

    it('makes the cheapest picks free', function (): void {
        $line = pricingQuote(102, [1 => [11], 2 => [21, 22, 24]], toppings: SelectionRules::upTo(3, freeCount: 1));

        expect($line->unitPrice()->poisha())->toBe(99_000) // 850 + 60 + 80, Jalapeños (50) free
            ->and(array_map(fn (PricedOption $o): string => "{$o->optionName()} {$o->charged()->poisha()}", $line->options()))
            ->toBe(['Regular 0', 'Olives 6000', 'Mushrooms 8000', 'Jalapeños 0'])
            ->and($line->options()[3]->isFreePick())->toBeTrue();
    });

    it('makes the lower id free when two picks cost the same', function (): void {
        $line = pricingQuote(102, [1 => [11], 2 => [25, 24]], toppings: SelectionRules::upTo(3, freeCount: 1));

        expect($line->unitPrice()->poisha())->toBe(90_000) // 850 + Corn 50, Jalapeños (id 24) free
            ->and($line->options()[1]->optionName())->toBe('Jalapeños')
            ->and($line->options()[1]->isFreePick())->toBeTrue();
    });

    it('never spends a free pick on an option that is free anyway', function (): void {
        $line = pricingQuote(102, [1 => [11], 2 => [21, 26]], toppings: SelectionRules::upTo(3, freeCount: 1));

        expect($line->unitPrice()->poisha())->toBe(85_000) // Basil is free anyway, so Olives (60) are the free pick
            ->and($line->options()[1]->optionName())->toBe('Olives')
            ->and($line->options()[1]->isFreePick())->toBeTrue()
            ->and($line->options()[2]->optionName())->toBe('Basil')
            ->and($line->options()[2]->isFreePick())->toBeFalse();
    });

    it('multiplies the unit price by the quantity', function (): void {
        $line = pricingQuote(103, [1 => [13], 2 => [23]], quantity: 3);

        expect($line->unitPrice()->poisha())->toBe(165_000) // 1250 + 200 + 200
            ->and($line->lineTotal()->poisha())->toBe(495_000)
            ->and($line->quantity()->value())->toBe(3);
    });

    it('explains the price in readable lines', function (): void {
        $line = pricingQuote(103, [1 => [13], 2 => [24]], quantity: 2, toppings: SelectionRules::upTo(3, freeCount: 1));

        expect($line->breakdown())->toBe([
            'Margherita (Large): ৳1250.00',
            'Crust: Stuffed +৳200.00',
            'Extra toppings: Jalapeños free',
            'Each: ৳1450.00',
            'Total: 2 × ৳1450.00 = ৳2900.00',
        ]);
    });
});

describe('group rules', function (): void {
    it('refuses a line without a pick from a required group', function (): void {
        expect(pricingFailure(fn () => pricingQuote(102, [2 => [21]])))->toBe(PricingFailureReason::MissingRequiredGroup);
    });

    it('refuses more picks than the maximum', function (): void {
        expect(pricingFailure(fn () => pricingQuote(102, [1 => [11, 12]])))->toBe(PricingFailureReason::TooManyChoices)
            ->and(pricingFailure(fn () => pricingQuote(102, [1 => [11], 2 => [21, 22, 23, 24]])))->toBe(PricingFailureReason::TooManyChoices);
    });

    it('refuses fewer picks than the minimum, but allows none from an optional group', function (): void {
        $twoOrThree = SelectionRules::of(isRequired: false, minimum: 2, maximum: 3);

        expect(pricingFailure(fn () => pricingQuote(102, [1 => [11], 2 => [21]], toppings: $twoOrThree)))->toBe(PricingFailureReason::TooFewChoices)
            ->and(pricingQuote(102, [1 => [11]], toppings: $twoOrThree)->unitPrice()->poisha())->toBe(85_000);
    });

    it('refuses an option of another group', function (): void {
        expect(pricingFailure(fn () => pricingQuote(102, [1 => [21]])))->toBe(PricingFailureReason::OptionNotInGroup);
    });

    it('refuses a group that is not attached to the product', function (): void {
        expect(pricingFailure(fn () => pricingQuote(102, [1 => [11], 3 => [31]])))->toBe(PricingFailureReason::GroupNotOffered);
    });

    it('treats an inactive group as not attached', function (): void {
        expect(pricingQuote(102, [], crustActive: false)->unitPrice()->poisha())->toBe(85_000)
            ->and(pricingFailure(fn () => pricingQuote(102, [1 => [11]], crustActive: false)))->toBe(PricingFailureReason::GroupNotOffered);
    });
});

describe('availability', function (): void {
    it('refuses a sold-out option', function (): void {
        expect(pricingFailure(fn () => pricingQuote(102, [1 => [15]])))->toBe(PricingFailureReason::OptionSoldOut);
    });

    it('refuses an option that has no price for the chosen size', function (): void {
        expect(pricingFailure(fn () => pricingQuote(101, [1 => [14]])))->toBe(PricingFailureReason::OptionNotOfferedForSize)
            ->and(pricingFailure(fn () => pricingQuote(104, [1 => [13]])))->toBe(PricingFailureReason::OptionNotOfferedForSize)
            ->and(pricingQuote(104, [1 => [11]])->unitPrice()->poisha())->toBe(15_000);
    });

    it('refuses a sold-out size', function (): void {
        expect(pricingFailure(fn () => pricingQuote(103, [1 => [11]], product: pricingMargherita(largeAvailable: false))))
            ->toBe(PricingFailureReason::VariantSoldOut);
    });

    it('refuses a size the product does not have', function (): void {
        expect(pricingFailure(fn () => pricingQuote(999, [1 => [11]])))->toBe(PricingFailureReason::UnknownVariant);
    });

    it('refuses a product that is not published', function (): void {
        expect(pricingFailure(fn () => pricingQuote(102, [1 => [11]], product: pricingMargherita(isPublished: false))))
            ->toBe(PricingFailureReason::ProductUnpublished);
    });

    it('refuses a product that is sold out', function (): void {
        expect(pricingFailure(fn () => pricingQuote(102, [1 => [11]], product: pricingMargherita(isAvailable: false))))
            ->toBe(PricingFailureReason::ProductSoldOut);
    });

    it('refuses choices made for another product', function (): void {
        $selection = LineSelection::of(11, 102, [1 => [11]], Quantity::of(1));

        expect(pricingFailure(fn () => (new PricingEngine)->price(pricingMargherita(), $selection, pricingAttachments(), pricingGroups())))
            ->toBe(PricingFailureReason::WrongProduct);
    });
});
