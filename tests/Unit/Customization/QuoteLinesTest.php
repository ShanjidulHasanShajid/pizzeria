<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\QuotedLine;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\UseCases\QuoteLines;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Services\PricingEngine;
use App\Modules\Customization\Domain\ValueObjects\PricingProduct;
use App\Modules\Customization\Domain\ValueObjects\PricingVariant;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Customization\Fakes\InMemoryOptionGroupRepository;
use Tests\Unit\Customization\Fakes\InMemoryPricingProductSource;
use Tests\Unit\Customization\Fakes\InMemoryProductOptionGroupRepository;

/*
| Margherita (10): variant 101 ৳450, group "Crust" required: Regular ৳0 (option 1), Stuffed ৳150 (option 2).
| Cola (20): variant 201 ৳60, no options.
*/
beforeEach(function (): void {
    $products = new InMemoryPricingProductSource;
    $products->add(new PricingProduct(10, 'Margherita', true, true, [new PricingVariant(101, 'Personal', Money::fromTaka('450'), 1, true)]));
    $products->add(new PricingProduct(20, 'Cola', true, true, [new PricingVariant(201, '500 ml', Money::fromTaka('60'), null, true)]));

    $groups = new InMemoryOptionGroupRepository;
    $crust = OptionGroup::create('Crusts', 'Crust', SortOrder::first());
    $crust->addOption(Option::withFlatPrice('Regular', Money::zero(), SortOrder::first()));
    $crust->addOption(Option::withFlatPrice('Stuffed', Money::fromTaka('150'), SortOrder::fromInt(20)));
    $groups->save($crust);

    $attachments = new InMemoryProductOptionGroupRepository;
    $attachments->save(ProductOptionGroup::attach(10, 1, SelectionRules::exactlyOne(), SortOrder::first()));

    $useCase = new QuoteLines($products, $attachments, $groups, new PricingEngine);

    /** @return array<int, QuotedLine> */
    $this->quote = fn (array $inputs): array => $useCase->handle($inputs);
});

it('prices several lines and keeps their keys', function (): void {
    $quotes = ($this->quote)([
        5 => new QuoteLineInput(10, 101, [1 => [2]], 2),
        9 => new QuoteLineInput(20, 201, [], 1),
        12 => new QuoteLineInput(10, 101, [1 => [1]], 1),
    ]);

    expect(array_keys($quotes))->toBe([5, 9, 12])
        ->and($quotes[5]->line->lineTotal->poisha())->toBe(120_000)
        ->and($quotes[9]->line->lineTotal->poisha())->toBe(6_000)
        ->and($quotes[12]->line->lineTotal->poisha())->toBe(45_000);
});

it('does not demand the options of one product from another', function (): void {
    expect(($this->quote)([
        new QuoteLineInput(10, 101, [1 => [1]], 1),
        new QuoteLineInput(20, 201, [], 1),
    ])[1]->problem)->toBeNull();
});

it('answers with a problem and still prices the other lines', function (): void {
    $quotes = ($this->quote)([
        new QuoteLineInput(10, 101, [], 1),          // no crust
        new QuoteLineInput(99, 1, [], 1),            // unknown product
        new QuoteLineInput(20, 999, [], 1),          // unknown size
        new QuoteLineInput(20, 201, [1 => [1]], 1),  // an option of another product
        new QuoteLineInput(20, 201, [], 0),          // impossible quantity
        new QuoteLineInput(20, 201, [], 3),
    ]);

    expect($quotes[0]->problem)->toBe('Please choose: Crust.')
        ->and($quotes[0]->line)->toBeNull()
        ->and($quotes[1]->problem)->toContain('no longer on the menu')
        ->and($quotes[2]->problem)->toContain('Choose a size')
        ->and($quotes[3]->problem)->toContain('not offered')
        ->and($quotes[4]->problem)->toContain('between 1 and 20')
        ->and($quotes[5]->line->lineTotal->poisha())->toBe(18_000);
});

it('answers nothing with nothing', function (): void {
    expect(($this->quote)([]))->toBe([]);
});
