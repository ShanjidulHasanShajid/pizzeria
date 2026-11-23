<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Ports\SizeTierPrices;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Application\Ports\CustomizableProducts;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Customization\Presentation\Support\OptionTrash;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->groups = app(OptionGroupRepository::class);
    $this->large = SizeTierModel::factory()->create()->id;

    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption(Option::withPricesPerSize('Stuffed', [$this->large => Money::fromTaka('200')], SortOrder::fromInt(10)));
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(20)));
    $this->crust = $this->groups->save($crust);
    [$this->stuffed, $this->thin] = array_map(fn (Option $option): int => $option->id(), $this->groups->find($this->crust)->options());

    $this->removeOption = function (int $optionId): void {
        $crust = $this->groups->find($this->crust);
        $crust->removeOption($optionId);
        $this->groups->save($crust);
    };
});

it('finds a deleted option, knows its group, and brings it back with its prices', function (): void {
    ($this->removeOption)($this->stuffed);

    expect($this->groups->findTrashedOption($this->stuffed)->name())->toBe('Stuffed')
        ->and($this->groups->groupIdOfTrashedOption($this->stuffed))->toBe($this->crust)
        ->and($this->groups->findTrashedOption($this->thin))->toBeNull()
        ->and($this->groups->groupIdOfTrashedOption(424242))->toBeNull();

    $this->groups->restoreOption($this->stuffed);

    expect($this->groups->find($this->crust)->option($this->stuffed)->priceFor($this->large)->poisha())->toBe(20_000);
});

it('deletes an option for good, with its prices', function (): void {
    ($this->removeOption)($this->stuffed);

    $this->groups->forceDeleteOption($this->stuffed);

    $this->assertDatabaseMissing('options', ['id' => $this->stuffed]);
    $this->assertDatabaseCount('option_prices', 0);
});

it('deletes a group for good, with all its options, and knows every image path', function (): void {
    $crust = $this->groups->find($this->crust);
    $crust->option($this->stuffed)->changeImage('options/stuffed.webp');
    $crust->option($this->thin)->changeImage('options/thin.webp');
    $this->groups->save($crust);
    ($this->removeOption)($this->thin);
    $this->groups->delete($this->crust);

    expect($this->groups->findTrashed($this->crust)->name())->toBe('Crust')
        ->and($this->groups->imagePaths($this->crust))->toEqualCanonicalizing(['options/stuffed.webp', 'options/thin.webp']);

    $this->groups->forceDelete($this->crust);

    $this->assertDatabaseCount('option_groups', 0);
    $this->assertDatabaseCount('options', 0);
});

it('lists deleted groups and options for the Trash screen', function (): void {
    ($this->removeOption)($this->thin);
    $this->groups->delete($this->groups->save(OptionGroup::create('Old dips', 'Dips', SortOrder::fromInt(20))));

    $items = array_map(
        fn ($item): string => "{$item->kind}: {$item->name}",
        app(OptionTrash::class)->items(),
    );

    expect($items)->toBe(['option_group: Old dips', 'option: Thin (Crust)']);
});

it('tells the catalog which sizes option prices use', function (): void {
    $prices = app(SizeTierPrices::class);
    $unused = SizeTierModel::factory()->create()->id;

    expect($prices->usesSizeTier($this->large))->toBeTrue()
        ->and($prices->usesSizeTier($unused))->toBeFalse()
        ->and($prices->usesSizeTierIncludingTrash($unused))->toBeFalse();

    ($this->removeOption)($this->stuffed);

    expect($prices->usesSizeTier($this->large))->toBeFalse()
        ->and($prices->usesSizeTierIncludingTrash($this->large))->toBeTrue();
});

it('does not count the kept size prices of an option that is flat now', function (): void {
    $crust = $this->groups->find($this->crust);
    $crust->option($this->stuffed)->useFlatPrice(Money::fromTaka('120'));
    $this->groups->save($crust);

    expect(app(SizeTierPrices::class)->usesSizeTier($this->large))->toBeFalse()
        ->and(app(SizeTierPrices::class)->usesSizeTierIncludingTrash($this->large))->toBeTrue();
});

it('lists the groups of a product for the product form, inactive ones too', function (): void {
    $product = ProductModel::factory()->create();
    $dips = OptionGroup::create('Dips', 'Dips', SortOrder::fromInt(20));
    $dips->deactivate();
    $dipsId = $this->groups->save($dips);
    $attachments = app(ProductOptionGroupRepository::class);
    $crustAttachment = $attachments->save(ProductOptionGroup::attach($product->id, $this->crust, SelectionRules::exactlyOne(), SortOrder::fromInt(20)));
    $attachments->save(ProductOptionGroup::attach($product->id, $dipsId, SelectionRules::upTo(3, freeCount: 1), SortOrder::fromInt(10)));

    [$first, $second] = app(AdminOptionQuery::class)->attachedGroups($product->id);

    expect($first->groupName)->toBe('Dips')
        ->and($first->isGroupActive)->toBeFalse()
        ->and($first->maximum)->toBe(3)
        ->and($first->freeCount)->toBe(1)
        ->and($second->attachmentId)->toBe($crustAttachment)
        ->and($second->optionCount)->toBe(2)
        ->and($second->isRequired)->toBeTrue();
});

it('lists all groups with their options, and the categories for the bulk attach', function (): void {
    $drinks = CategoryModel::factory()->create(['name' => 'Drinks']);
    $coffee = CategoryModel::factory()->childOf($drinks)->create(['name' => 'Coffee']);

    $groups = app(AdminOptionQuery::class)->groups();

    expect($groups)->toHaveCount(1)
        ->and($groups[0]->options)->toHaveCount(2)
        ->and(app(CustomizableProducts::class)->categoryNames())->toBe([$drinks->id => 'Drinks', $coffee->id => 'Drinks > Coffee']);
});
