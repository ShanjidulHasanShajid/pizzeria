<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function (): void {
    $this->repository = app(OptionGroupRepository::class);
    $this->medium = SizeTierModel::factory()->create()->id;
    $this->large = SizeTierModel::factory()->create()->id;

    $this->saveCrust = function (): int {
        $crust = OptionGroup::create('Pizza crusts', 'Crust', SortOrder::fromInt(10), 'Baked fresh.');
        $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(10), isVeg: true));
        $crust->addOption(Option::withPricesPerSize('Stuffed', [
            $this->medium => Money::fromTaka('150'),
            $this->large => Money::fromTaka('200'),
        ], SortOrder::fromInt(20)));

        return $this->repository->save($crust);
    };
});

it('saves a new group with its options and prices and loads it again', function (): void {
    $id = ($this->saveCrust)();

    $crust = $this->repository->find($id);
    [$thin, $stuffed] = $crust->options();

    expect($crust->id())->toBe($id)
        ->and($crust->name())->toBe('Pizza crusts')
        ->and($crust->displayTitle())->toBe('Crust')
        ->and($crust->description())->toBe('Baked fresh.')
        ->and($crust->isActive())->toBeTrue()
        ->and($crust->sort()->value())->toBe(10)
        ->and($thin->name())->toBe('Thin')
        ->and($thin->isVeg())->toBeTrue()
        ->and($thin->priceMode())->toBe(PriceMode::Flat)
        ->and($thin->flatPrice()->isZero())->toBeTrue()
        ->and($stuffed->priceMode())->toBe(PriceMode::PerSize)
        ->and($stuffed->flatPrice())->toBeNull()
        ->and($stuffed->priceFor($this->medium)->poisha())->toBe(15_000)
        ->and($stuffed->priceFor($this->large)->poisha())->toBe(20_000);
});

it('updates a group in place: options keep their ids, and a removed size price is gone', function (): void {
    $id = ($this->saveCrust)();
    $crust = $this->repository->find($id);
    [$thin, $stuffed] = $crust->options();

    $crust->retitle('Choose your crust');
    $thin->markSoldOut();
    $stuffed->usePricesPerSize([$this->large => Money::fromTaka('220')]);
    $this->repository->save($crust);

    $saved = $this->repository->find($id);

    expect($saved->displayTitle())->toBe('Choose your crust')
        ->and($saved->option($thin->id())->isAvailable())->toBeFalse()
        ->and($saved->option($stuffed->id())->priceFor($this->medium))->toBeNull()
        ->and($saved->option($stuffed->id())->priceFor($this->large)->poisha())->toBe(22_000);
    $this->assertDatabaseCount('options', 2);
    $this->assertDatabaseCount('option_prices', 1);
});

it('moves an option that is no longer in the group to the Trash', function (): void {
    $id = ($this->saveCrust)();
    $crust = $this->repository->find($id);
    [$thin, $stuffed] = $crust->options();

    $this->repository->save(OptionGroup::reconstitute(
        $id, $crust->name(), $crust->displayTitle(), null, true, $crust->sort(), [$thin],
    ));

    expect($this->repository->find($id)->options())->toHaveCount(1);
    $this->assertSoftDeleted('options', ['id' => $stuffed->id()]);
});

it('moves a group to the Trash and back, with its options', function (): void {
    $id = ($this->saveCrust)();

    $this->repository->delete($id);

    expect($this->repository->find($id))->toBeNull();
    $this->assertSoftDeleted('option_groups', ['id' => $id]);

    $this->repository->restore($id);

    expect($this->repository->find($id)->options())->toHaveCount(2);
});

it('finds several groups in sort order and leaves out deleted and unknown ones', function (): void {
    $dips = $this->repository->save(OptionGroup::create('Dips', 'Dips', SortOrder::fromInt(30)));
    $sauce = $this->repository->save(OptionGroup::create('Sauces', 'Sauce', SortOrder::fromInt(20)));
    $old = $this->repository->save(OptionGroup::create('Old', 'Old', SortOrder::fromInt(10)));
    $this->repository->delete($old);

    $found = $this->repository->findByIds([$dips, $sauce, $old, 424242]);

    expect(array_map(fn (OptionGroup $group): string => $group->name(), $found))->toBe(['Sauces', 'Dips'])
        ->and($this->repository->findByIds([]))->toBe([]);
});

it('returns null for an unknown id and refuses to delete it', function (): void {
    expect($this->repository->find(424242))->toBeNull();
    expect(fn () => $this->repository->delete(424242))->toThrow(ModelNotFoundException::class);
});
