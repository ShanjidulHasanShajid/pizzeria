<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The sample option groups. Prices in taka are examples.
 * A number is one price for every size; three numbers are Personal, Medium and Large.
 */
final class OptionGroupSeeder extends Seeder
{
    private const VEG = true;

    private const NON_VEG = false;

    /** name => [display title, [option => [price, veg]]] */
    private const GROUPS = [
        'Crust' => ['Choose your crust', [
            'Regular' => [0, self::VEG],
            'Thin' => [0, self::VEG],
            'Stuffed' => [[100, 150, 200], self::VEG],
            'Cheese-filled' => [[120, 180, 250], self::VEG],
        ]],
        'Sauce' => ['Choose your sauce', [
            'Tomato' => [0, self::VEG],
            'BBQ' => [0, self::VEG],
            'Garlic cream' => [[30, 40, 50], self::VEG],
            'Naga chilli' => [[30, 40, 50], self::VEG],
        ]],
        'Cheese' => ['Extra cheese', [
            'Mozzarella' => [[80, 120, 160], self::VEG],
            'Cheddar' => [[80, 120, 160], self::VEG],
            'Parmesan' => [[90, 140, 180], self::VEG],
        ]],
        'Meat' => ['Choose your meat', [
            'Beef pepperoni' => [[100, 150, 200], self::NON_VEG],
            'Grilled chicken' => [[90, 140, 190], self::NON_VEG],
            'Minced beef' => [[100, 150, 200], self::NON_VEG],
            'Chicken sausage' => [[90, 140, 190], self::NON_VEG],
        ]],
        'Vegetables' => ['Choose your vegetables', [
            'Onion' => [[30, 45, 60], self::VEG],
            'Capsicum' => [[30, 45, 60], self::VEG],
            'Tomato' => [[30, 45, 60], self::VEG],
            'Sweet corn' => [[30, 45, 60], self::VEG],
            'Mushrooms' => [[40, 60, 80], self::VEG],
        ]],
        'Extra Toppings' => ['Extra toppings', [
            'Olives' => [[40, 60, 80], self::VEG],
            'Jalapenos' => [[40, 60, 80], self::VEG],
            'Mushrooms' => [[40, 60, 80], self::VEG],
            'Pineapple' => [[40, 60, 80], self::VEG],
            'Beef pepperoni' => [[100, 150, 200], self::NON_VEG],
            'Grilled chicken' => [[90, 140, 190], self::NON_VEG],
        ]],
        'Seasonings' => ['Seasonings', [
            'Oregano' => [0, self::VEG],
            'Chilli flakes' => [0, self::VEG],
            'Garlic powder' => [0, self::VEG],
            'Parmesan dust' => [20, self::VEG],
        ]],
        'Dips' => ['Add a dip', [
            'Garlic mayo' => [40, self::VEG],
            'BBQ' => [40, self::VEG],
            'Ranch' => [50, self::VEG],
            'Cheese' => [50, self::VEG],
        ]],
        'Wing Flavours' => ['Choose your flavour', [
            'Buffalo' => [0, self::VEG],
            'BBQ' => [0, self::VEG],
            'Honey garlic' => [0, self::VEG],
            'Naga chilli' => [20, self::VEG],
        ]],
        'Salad Dressings' => ['Choose your dressing', [
            'Caesar' => [0, self::NON_VEG],
            'Vinaigrette' => [0, self::VEG],
            'Ranch' => [0, self::VEG],
            'Honey mustard' => [0, self::VEG],
        ]],
        'Drink Extras' => ['Make it special', [
            'Whipped cream' => [40, self::VEG],
            'Chocolate syrup' => [30, self::VEG],
            'Extra espresso shot' => [60, self::VEG],
            'Scoop of ice cream' => [80, self::VEG],
        ]],
    ];

    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly AdminCatalogQuery $catalog,
    ) {}

    public function run(): void
    {
        $sizeTierIds = $this->sizeTierIds();
        $groupSort = SortOrder::first();

        foreach (self::GROUPS as $name => [$title, $options]) {
            $group = OptionGroup::create($name, $title, $groupSort);

            foreach ($options as $optionName => [$price, $isVeg]) {
                $group->addOption(is_array($price)
                    ? Option::withPricesPerSize($optionName, $this->sizePrices($price, $sizeTierIds), $group->nextOptionSort(), $isVeg)
                    : Option::withFlatPrice($optionName, Money::fromTaka($price), $group->nextOptionSort(), $isVeg));
            }

            $this->groups->save($group);
            $groupSort = $groupSort->next();
        }
    }

    /**
     * @return array{int, int, int} the ids of Personal, Medium and Large
     */
    private function sizeTierIds(): array
    {
        $ids = [];

        foreach ($this->catalog->sizeTierTable() as $row) {
            $ids[$row->slug] = $row->id;
        }

        if (! isset($ids['personal'], $ids['medium'], $ids['large'])) {
            throw new RuntimeException('The sizes are missing. Run the catalog seeders first.');
        }

        return [$ids['personal'], $ids['medium'], $ids['large']];
    }

    /**
     * @param  array{int, int, int}  $taka  Personal, Medium, Large
     * @param  array{int, int, int}  $sizeTierIds
     * @return array<int, Money>
     */
    private function sizePrices(array $taka, array $sizeTierIds): array
    {
        return array_combine($sizeTierIds, array_map(fn (int $amount): Money => Money::fromTaka($amount), $taka));
    }
}
