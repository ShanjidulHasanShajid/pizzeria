<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Infrastructure\Database\Seeders\Support\MenuItemWriter;
use Illuminate\Database\Seeder;

/**
 * The pizza list from docs/website-plan.md 5.4. Prices in taka are examples.
 */
final class PizzaSeeder extends Seeder
{
    private const CHEESE_AND_DOUGH = ['contains-dairy', 'contains-gluten'];

    public function __construct(private readonly MenuItemWriter $menu) {}

    public function run(): void
    {
        $base = self::CHEESE_AND_DOUGH;
        $meat = ['non-veg', 'halal', ...$base];

        $this->menu->add('pizza', 'Margherita', 'Tomato sauce, mozzarella and fresh basil.', $this->menu->sized(450, 850, 1250), ['veg', ...$base]);
        $this->menu->add('pizza', 'Pepperoni', 'Beef pepperoni and plenty of mozzarella.', $this->menu->sized(550, 990, 1450), $meat, isHot: true);
        $this->menu->add('pizza', 'Chicken BBQ', 'Smoky BBQ chicken, red onion and mozzarella.', $this->menu->sized(550, 990, 1450), $meat);
        $this->menu->add('pizza', 'Chicken Tikka', 'Tandoori chicken tikka, green chilli and onion.', $this->menu->sized(550, 990, 1450), ['spicy', ...$meat], isHot: true);
        $this->menu->add('pizza', 'Beef', 'Seasoned minced beef, peppers and onion.', $this->menu->sized(590, 1050, 1550), $meat);
        $this->menu->add('pizza', 'Meat Lovers', 'Beef, chicken, pepperoni and sausage.', $this->menu->sized(650, 1150, 1650), $meat);
        $this->menu->add('pizza', 'Seafood', 'Prawns, squid and garlic butter.', $this->menu->sized(690, 1190, 1690), ['non-veg', 'seafood', ...$base]);
        $this->menu->add('pizza', 'Four Cheese', 'Mozzarella, cheddar, parmesan and blue cheese.', $this->menu->sized(590, 1050, 1550), ['veg', ...$base]);
        $this->menu->add('pizza', 'Vegetarian', 'Peppers, olives, onion, corn and tomato.', $this->menu->sized(490, 890, 1290), ['veg', ...$base]);
        $this->menu->add('pizza', 'Mushroom', 'Button mushrooms, garlic and mozzarella.', $this->menu->sized(490, 890, 1290), ['veg', ...$base]);
        $this->menu->add('pizza', 'Hawaiian', 'Chicken ham, pineapple and mozzarella.', $this->menu->sized(550, 990, 1450), $meat);
        $this->menu->add('pizza', 'Spicy Chicken', 'Hot-sauce chicken, jalapenos and onion.', $this->menu->sized(550, 990, 1450), ['spicy', ...$meat], isNew: true);
    }
}
