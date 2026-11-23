<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Application\DTOs\SavePriceMatrixInput;
use App\Modules\Customization\Application\UseCases\Options\SavePriceMatrix;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('saves the price matrix and loads exactly the same prices again', function (): void {
    $medium = SizeTierModel::factory()->create()->id;
    $large = SizeTierModel::factory()->create()->id;
    $groups = app(OptionGroupRepository::class);
    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption(Option::withPricesPerSize('Stuffed', [], SortOrder::fromInt(10)));
    $crust->addOption(Option::withPricesPerSize('Cheese-filled', [$medium => Money::fromTaka('99')], SortOrder::fromInt(20)));
    $id = $groups->save($crust);
    [$stuffed, $cheeseFilled] = $groups->find($id)->options();

    app(SavePriceMatrix::class)->handle(new SavePriceMatrixInput($id, [
        $stuffed->id() => [$medium => '150', $large => '200.50'],
        $cheeseFilled->id() => [$medium => '', $large => '0'],
    ]));

    $prices = fn (int $optionId): array => array_map(
        fn (Money $price): int => $price->poisha(),
        $groups->find($id)->option($optionId)->sizePrices(),
    );

    expect($prices($stuffed->id()))->toBe([$medium => 15_000, $large => 20_050])
        ->and($prices($cheeseFilled->id()))->toBe([$large => 0]);
});
