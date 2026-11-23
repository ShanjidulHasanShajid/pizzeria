<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\PricedLineView;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\UseCases\QuoteLine;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Enums\PricingFailureReason;
use App\Modules\Customization\Domain\Exceptions\PricingFailed;
use App\Modules\Customization\Domain\Services\PricingEngine;
use App\Modules\Customization\Domain\ValueObjects\PricingProduct;
use App\Modules\Customization\Domain\ValueObjects\PricingVariant;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Customization\Fakes\InMemoryOptionGroupRepository;
use Tests\Unit\Customization\Fakes\InMemoryPricingProductSource;
use Tests\Unit\Customization\Fakes\InMemoryProductOptionGroupRepository;

/*
| Margherita (product 10): Medium ৳850 (variant 102, size tier 2).
| Group 1 "Crust", required: Regular ৳0 (option 1), Stuffed ৳150 at Medium (option 2).
| Group 2 "Extra toppings", up to 3, 1 free: Olives ৳60 (option 3), Corn ৳50 (option 4).
*/
beforeEach(function (): void {
    $products = new InMemoryPricingProductSource;
    $products->add(new PricingProduct(10, 'Margherita', true, true, [
        new PricingVariant(102, 'Medium', Money::fromTaka('850'), 2, true),
    ]));

    $this->groups = new InMemoryOptionGroupRepository;
    $crust = OptionGroup::create('Pizza crusts', 'Crust', SortOrder::first());
    $crust->addOption(Option::withFlatPrice('Regular', Money::zero(), SortOrder::first()));
    $crust->addOption(Option::withPricesPerSize('Stuffed', [2 => Money::fromTaka('150')], SortOrder::fromInt(20)));
    $this->groups->save($crust);
    $toppings = OptionGroup::create('Pizza toppings', 'Extra toppings', SortOrder::fromInt(20));
    $toppings->addOption(Option::withFlatPrice('Olives', Money::fromTaka('60'), SortOrder::first()));
    $toppings->addOption(Option::withFlatPrice('Corn', Money::fromTaka('50'), SortOrder::fromInt(20)));
    $this->groups->save($toppings);

    $attachments = new InMemoryProductOptionGroupRepository;
    $attachments->save(ProductOptionGroup::attach(10, 1, SelectionRules::exactlyOne(), SortOrder::first()));
    $attachments->save(ProductOptionGroup::attach(10, 2, SelectionRules::upTo(3, freeCount: 1), SortOrder::fromInt(20)));

    $useCase = new QuoteLine($products, $attachments, $this->groups, new PricingEngine);
    $this->quote = fn (array $choices, int $quantity = 1, int $productId = 10): PricedLineView => $useCase
        ->handle(new QuoteLineInput($productId, 102, $choices, $quantity));
});

it('prices a line from the stored prices and returns plain data', function (): void {
    $line = ($this->quote)([1 => [2], 2 => [3, 4]], quantity: 2);

    expect($line->productName)->toBe('Margherita')
        ->and($line->variantLabel)->toBe('Medium')
        ->and($line->basePrice->poisha())->toBe(85_000)
        ->and($line->unitPrice->poisha())->toBe(106_000) // 850 + 150 + 60, Corn (50) free
        ->and($line->quantity)->toBe(2)
        ->and($line->lineTotal->poisha())->toBe(212_000)
        ->and($line->breakdown)->toContain('Extra toppings: Corn free');
});

it('shows what every chosen option costs and which one is free', function (): void {
    $options = ($this->quote)([1 => [2], 2 => [3, 4]])->options;

    expect($options)->toHaveCount(3)
        ->and($options[0]->optionName)->toBe('Stuffed')
        ->and($options[0]->charged->poisha())->toBe(15_000)
        ->and($options[2]->optionName)->toBe('Corn')
        ->and($options[2]->price->poisha())->toBe(5_000)
        ->and($options[2]->charged->isZero())->toBeTrue()
        ->and($options[2]->isFreePick)->toBeTrue();
});

it('uses a changed price at the next quote', function (): void {
    $crust = $this->groups->find(1);
    $crust->option(2)->usePricesPerSize([2 => Money::fromTaka('175')]);
    $this->groups->save($crust);

    expect(($this->quote)([1 => [2]])->unitPrice->poisha())->toBe(102_500);
});

it('reports an unknown product', function (): void {
    ($this->quote)([1 => [1]], productId: 99);
})->throws(EntityNotFound::class);

it('passes on why the engine refuses a line', function (): void {
    try {
        ($this->quote)([2 => [3]]);
    } catch (PricingFailed $failed) {
        expect($failed->reason)->toBe(PricingFailureReason::MissingRequiredGroup);

        return;
    }

    $this->fail('A line without a crust must be refused.');
});

it('refuses a quantity or an id that cannot exist', function (): void {
    expect(fn () => ($this->quote)([1 => [1]], quantity: 0))->toThrow(InvalidValue::class);
    expect(fn () => ($this->quote)([1 => [0]]))->toThrow(InvalidValue::class);
});
