<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Application\DTOs\AdminOptionRow;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->query = app(AdminOptionQuery::class);
    $this->groups = app(OptionGroupRepository::class);
});

it('lists the groups in sort order with their option, sold-out and product counts', function (): void {
    $dips = OptionGroup::create('Dips', 'Add a dip', SortOrder::fromInt(20));
    $garlic = Option::withFlatPrice('Garlic', Money::fromTaka('40'), SortOrder::first());
    $garlic->markSoldOut();
    $dips->addOption($garlic);
    $dips->addOption(Option::withFlatPrice('BBQ', Money::fromTaka('40'), SortOrder::fromInt(20)));
    $dipsId = $this->groups->save($dips);
    $crust = OptionGroup::create('Crust', 'Your crust', SortOrder::fromInt(10));
    $crust->deactivate();
    $this->groups->save($crust);
    $this->groups->delete($this->groups->save(OptionGroup::create('Old', 'Old', SortOrder::fromInt(5))));
    app(ProductOptionGroupRepository::class)->save(
        ProductOptionGroup::attach(ProductModel::factory()->create()->id, $dipsId, SelectionRules::upTo(2), SortOrder::first()),
    );

    [$first, $second] = $this->query->groupTable();

    expect($this->query->groupTable())->toHaveCount(2)
        ->and($first->name)->toBe('Crust')
        ->and($first->isActive)->toBeFalse()
        ->and($first->optionCount)->toBe(0)
        ->and($first->productCount)->toBe(0)
        ->and($second->name)->toBe('Dips')
        ->and($second->displayTitle)->toBe('Add a dip')
        ->and($second->optionCount)->toBe(2)
        ->and($second->soldOutCount)->toBe(1)
        ->and($second->productCount)->toBe(1);
});

it('gives one group with its options in sort order and prices as taka texts', function (): void {
    $large = SizeTierModel::factory()->create()->id;
    $crust = OptionGroup::create('Crust', 'Your crust', SortOrder::first(), 'Baked fresh.');
    $crust->addOption(Option::withPricesPerSize('Stuffed', [$large => Money::fromTaka('200.50')], SortOrder::fromInt(20)));
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(10), isVeg: true));

    $group = $this->query->group($this->groups->save($crust));

    expect($group->description)->toBe('Baked fresh.')
        ->and(array_map(fn (AdminOptionRow $option): string => $option->name, $group->options))->toBe(['Thin', 'Stuffed'])
        ->and($group->options[0]->isVeg)->toBeTrue()
        ->and($group->options[0]->isPricedPerSize)->toBeFalse()
        ->and($group->options[0]->flatPriceTaka)->toBe('0.00')
        ->and($group->options[1]->isPricedPerSize)->toBeTrue()
        ->and($group->options[1]->flatPriceTaka)->toBeNull()
        ->and($group->options[1]->sizePricesTaka)->toBe([$large => '200.50']);
});

it('returns null for an unknown or deleted group', function (): void {
    $id = $this->groups->save(OptionGroup::create('Old', 'Old', SortOrder::first()));
    $this->groups->delete($id);

    expect($this->query->group($id))->toBeNull()
        ->and($this->query->group(424242))->toBeNull();
});
