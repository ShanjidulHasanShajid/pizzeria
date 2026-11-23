<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\SavePriceMatrixInput;
use App\Modules\Customization\Application\UseCases\Options\SavePriceMatrix;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Customization\Fakes\FixedSizeTiers;
use Tests\Unit\Customization\Fakes\InMemoryOptionGroupRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

/*
| Sizes 1, 2 and 3 exist. Group 1: Stuffed (option 1, per size: size 2 ৳150, deleted size 9 ৳500),
| Cheese-filled (option 2, per size, no prices), Thin (option 3, flat ৳0).
*/
beforeEach(function (): void {
    $this->groups = new InMemoryOptionGroupRepository;
    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption(Option::withPricesPerSize('Stuffed', [2 => Money::fromTaka('150'), 9 => Money::fromTaka('500')], SortOrder::first()));
    $crust->addOption(Option::withPricesPerSize('Cheese-filled', [], SortOrder::fromInt(20)));
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(30)));
    $this->groups->save($crust);

    $useCase = new SavePriceMatrix($this->groups, new FixedSizeTiers([1, 2, 3]), new PassThroughTransactionManager);
    $this->save = fn (array $cells, int $groupId = 1) => $useCase->handle(new SavePriceMatrixInput($groupId, $cells));
});

it('saves all rows at once: empty means not offered, zero means free', function (): void {
    ($this->save)([
        1 => [1 => '100', 2 => '', 3 => '200.50'],
        2 => [1 => null, 2 => '0', 3 => '250'],
    ]);

    $stuffed = $this->groups->find(1)->option(1);
    $cheeseFilled = $this->groups->find(1)->option(2);

    expect($stuffed->priceFor(1)->poisha())->toBe(10_000)
        ->and($stuffed->priceFor(2))->toBeNull()
        ->and($stuffed->priceFor(3)->poisha())->toBe(20_050)
        ->and($cheeseFilled->priceFor(1))->toBeNull()
        ->and($cheeseFilled->priceFor(2)->isZero())->toBeTrue()
        ->and($cheeseFilled->priceFor(3)->poisha())->toBe(25_000);
});

it('keeps the prices of cells that were not sent, for example of a deleted size', function (): void {
    ($this->save)([1 => [1 => '100']]);

    $stuffed = $this->groups->find(1)->option(1);

    expect($stuffed->priceFor(2)->poisha())->toBe(15_000)
        ->and($stuffed->priceFor(9)->poisha())->toBe(50_000);
});

it('reports every wrong cell at once and saves nothing', function (): void {
    try {
        ($this->save)([
            1 => [1 => 'abc', 2 => '175'],
            2 => [2 => '-5', 9 => '10'],
            3 => [1 => '10'],
        ]);
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe(['prices.1.1', 'prices.2.2', 'prices.2.9', 'prices.3'])
            ->and($this->groups->find(1)->option(1)->priceFor(2)->poisha())->toBe(15_000);

        return;
    }

    throw new RuntimeException('Wrong cells must be refused.');
});

it('reports an unknown group or option', function (): void {
    expect(fn () => ($this->save)([1 => [1 => '10']], groupId: 99))->toThrow(EntityNotFound::class);
    expect(fn () => ($this->save)([99 => [1 => '10']]))->toThrow(EntityNotFound::class);
});
