<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Application\DTOs\OptionView;
use App\Modules\Customization\Application\DTOs\ProductOptionGroupView;
use App\Modules\Customization\Application\Queries\ProductOptionsQuery;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->query = app(ProductOptionsQuery::class);
    $this->groups = app(OptionGroupRepository::class);
    $this->product = ProductModel::factory()->create();
    $this->large = SizeTierModel::factory()->create()->id;

    // Saves a group with the given options and attaches it to the product.
    $this->offer = function (OptionGroup $group, SelectionRules $rules, int $sort): int {
        $id = $this->groups->save($group);
        app(ProductOptionGroupRepository::class)->save(
            ProductOptionGroup::attach($this->product->id, $id, $rules, SortOrder::fromInt($sort)),
        );

        return $id;
    };
});

it('lists the groups in the product order with their rules, options and prices', function (): void {
    $toppings = OptionGroup::create('Pizza toppings', 'Extra toppings', SortOrder::first(), 'Pick your favourites.');
    $corn = Option::withFlatPrice('Corn', Money::fromTaka('50'), SortOrder::fromInt(20), isVeg: true);
    $corn->markSoldOut();
    $toppings->addOption($corn);
    $toppings->addOption(Option::withFlatPrice('Olives', Money::fromTaka('60'), SortOrder::fromInt(10)));
    ($this->offer)($toppings, SelectionRules::upTo(3, freeCount: 1), sort: 20);

    $crust = OptionGroup::create('Pizza crusts', 'Crust', SortOrder::fromInt(90));
    $crust->addOption(Option::withPricesPerSize('Stuffed', [$this->large => Money::fromTaka('200')], SortOrder::first()));
    ($this->offer)($crust, SelectionRules::exactlyOne(), sort: 10);

    [$first, $second] = $this->query->forProduct($this->product->id);

    expect($first->title)->toBe('Crust')
        ->and($first->isRequired)->toBeTrue()
        ->and($first->minimum)->toBe(1)
        ->and($first->maximum)->toBe(1)
        ->and($first->options[0]->flatPrice)->toBeNull()
        ->and($first->options[0]->sizePrices[$this->large]->poisha())->toBe(20_000)
        ->and($second->title)->toBe('Extra toppings')
        ->and($second->description)->toBe('Pick your favourites.')
        ->and($second->isRequired)->toBeFalse()
        ->and($second->freeCount)->toBe(1)
        ->and(array_map(fn (OptionView $option): string => $option->name, $second->options))->toBe(['Olives', 'Corn'])
        ->and($second->options[0]->flatPrice->poisha())->toBe(6_000)
        ->and($second->options[0]->sizePrices)->toBe([])
        ->and($second->options[1]->isVeg)->toBeTrue()
        ->and($second->options[1]->isAvailable)->toBeFalse();
});

it('leaves out inactive groups, deleted groups and deleted options', function (): void {
    $inactive = OptionGroup::create('Old sauces', 'Sauce', SortOrder::first());
    $inactive->deactivate();
    ($this->offer)($inactive, SelectionRules::upTo(1), sort: 10);

    $deleted = ($this->offer)(OptionGroup::create('Old dips', 'Dips', SortOrder::first()), SelectionRules::upTo(1), sort: 20);
    $this->groups->delete($deleted);

    $crust = OptionGroup::create('Crusts', 'Crust', SortOrder::first());
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(10)));
    $crust->addOption(Option::withFlatPrice('Old crust', Money::zero(), SortOrder::fromInt(20)));
    $crustId = ($this->offer)($crust, SelectionRules::exactlyOne(), sort: 30);
    $saved = $this->groups->find($crustId);
    $this->groups->save(OptionGroup::reconstitute(
        $crustId, 'Crusts', 'Crust', null, true, SortOrder::first(), [$saved->options()[0]],
    ));

    $groups = $this->query->forProduct($this->product->id);

    expect(array_map(fn (ProductOptionGroupView $group): string => $group->title, $groups))->toBe(['Crust'])
        ->and(array_map(fn (OptionView $option): string => $option->name, $groups[0]->options))->toBe(['Thin']);
});

it('needs the same number of queries for one group as for five', function (): void {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->query->forProduct($this->product->id);

        return count(DB::getQueryLog());
    };
    $offerGroup = function (int $number): void {
        $group = OptionGroup::create("Group {$number}", "Group {$number}", SortOrder::fromInt($number * 10));
        $group->addOption(Option::withPricesPerSize('One', [$this->large => Money::zero()], SortOrder::first()));
        ($this->offer)($group, SelectionRules::upTo(1), sort: $number * 10);
    };

    $offerGroup(1);
    $forOne = $countQueries();

    foreach ([2, 3, 4, 5] as $number) {
        $offerGroup($number);
    }

    $forFive = $countQueries();

    expect($this->query->forProduct($this->product->id))->toHaveCount(5)
        ->and($forFive)->toBe($forOne);
});

it('knows which of many products have an active group', function (): void {
    $with = ProductModel::factory()->create();
    $without = ProductModel::factory()->create();
    $group = app(OptionGroupRepository::class)->save(OptionGroup::create('Dips', 'Dips', SortOrder::first()));
    app(ProductOptionGroupRepository::class)->save(ProductOptionGroup::attach($with->id, $group, SelectionRules::upTo(3), SortOrder::first()));

    expect($this->query->productIdsWithOptions([$without->id, $with->id, 424242]))->toBe([$with->id])
        ->and($this->query->productIdsWithOptions([$without->id]))->toBe([])
        ->and($this->query->productIdsWithOptions([]))->toBe([]);
});
