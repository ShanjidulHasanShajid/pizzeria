<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Infrastructure\Database\Seeders\Support\MenuItemWriter;
use Illuminate\Database\Seeder;

/**
 * Everything that is not a pizza or a pie. Prices in taka are examples.
 */
final class MenuSeeder extends Seeder
{
    public function __construct(private readonly MenuItemWriter $menu) {}

    public function run(): void
    {
        $this->sides();
        $this->pasta();
        $this->burgersAndSandwiches();
        $this->salads();
        $this->desserts();
        $this->drinks();
    }

    private function sides(): void
    {
        $fried = LeadTime::madeToOrder(15);
        $bread = ['veg', 'contains-gluten', 'contains-dairy'];
        $chicken = ['non-veg', 'halal'];

        $this->menu->add('sides', 'Garlic Bread', 'Toasted bread with garlic butter.', [['4 pcs', 180, null], ['8 pcs', 320, null]], $bread, $fried);
        $this->menu->add('sides', 'Cheese Garlic Bread', 'Garlic bread under melted mozzarella.', [['4 pcs', 240, null], ['8 pcs', 420, null]], $bread, $fried);
        $this->menu->add('sides', 'Breadsticks', 'Soft breadsticks with a garlic dip.', [['6 pcs', 220, null], ['12 pcs', 400, null]], $bread, $fried);
        $this->menu->add('sides', 'Chicken Wings', 'Crispy wings in your choice of sauce.', [['6 pcs', 390, null], ['12 pcs', 720, null], ['20 pcs', 1150, null]], $chicken, $fried, isHot: true);
        $this->menu->add('sides', 'Chicken Tenders', 'Breaded chicken strips with a dip.', [['4 pcs', 360, null], ['8 pcs', 680, null]], [...$chicken, 'contains-gluten'], $fried);
        $this->menu->add('sides', 'Mozzarella Sticks', 'Golden fried mozzarella with tomato dip.', [['6 pcs', 350, null]], $bread, $fried);
        $this->menu->add('sides', 'French Fries', 'Crispy salted fries.', [['Regular', 150, null], ['Large', 220, null]], ['veg'], $fried);
        $this->menu->add('sides', 'Potato Wedges', 'Seasoned potato wedges.', [['Regular', 180, null], ['Large', 260, null]], ['veg'], $fried);
        $this->menu->add('sides', 'Onion Rings', 'Battered onion rings.', [['Regular', 190, null], ['Large', 280, null]], ['veg', 'contains-gluten'], $fried);
        $this->menu->add('sides', 'Loaded Nachos', 'Tortilla chips, cheese sauce, salsa and jalapenos.', [['Regular', 320, null], ['Large', 480, null]], ['veg', 'spicy', 'contains-dairy'], $fried, isNew: true);
        $this->menu->add('sides', 'Jalapeno Poppers', 'Cream-cheese stuffed jalapenos.', [['6 pcs', 320, null]], ['veg', 'spicy', 'contains-dairy'], $fried);
    }

    private function pasta(): void
    {
        $pasta = ['contains-gluten', 'contains-dairy'];
        $meat = ['non-veg', 'halal', ...$pasta];

        $this->menu->add('pasta', 'Fettuccine Alfredo', 'Fettuccine in a creamy parmesan sauce.', [['Regular', 450, null], ['Family', 1150, null]], ['veg', ...$pasta]);
        $this->menu->add('pasta', 'Spaghetti Carbonara', 'Beef bacon, egg yolk and parmesan.', [['Regular', 480, null], ['Family', 1200, null]], $meat);
        $this->menu->add('pasta', 'Spaghetti Bolognese', 'Slow-cooked beef ragu.', [['Regular', 460, null], ['Family', 1150, null]], $meat);
        $this->menu->add('pasta', 'Penne Arrabbiata', 'Penne in a spicy tomato and garlic sauce.', [['Regular', 420, null], ['Family', 1050, null]], ['veg', 'spicy', 'contains-gluten']);
        $this->menu->add('pasta', 'Creamy Chicken Pasta', 'Penne with chicken in a garlic cream sauce.', [['Regular', 480, null], ['Family', 1200, null]], $meat);
        $this->menu->add('pasta', 'Baked Pasta', 'Pasta baked under bubbling cheese.', [['Regular', 520, null], ['Family', 1290, null]], $meat, LeadTime::madeToOrder(25));
        $this->menu->add('pasta', 'Beef Lasagna', 'Layers of pasta, beef ragu and bechamel.', [['Regular', 590, null], ['Family', 1450, null]], $meat, LeadTime::madeToOrder(35));
    }

    private function burgersAndSandwiches(): void
    {
        $grilled = ['non-veg', 'halal', 'contains-gluten'];

        $this->menu->add('burgers-and-sandwiches', 'Classic Beef Burger', 'Beef patty, cheese, lettuce and house sauce.', [['Regular', 420, null]], [...$grilled, 'contains-dairy']);
        $this->menu->add('burgers-and-sandwiches', 'Double Beef Burger', 'Two beef patties and double cheese.', [['Regular', 590, null]], [...$grilled, 'contains-dairy']);
        $this->menu->add('burgers-and-sandwiches', 'Crispy Chicken Burger', 'Fried chicken fillet, slaw and mayo.', [['Regular', 380, null]], $grilled);
        $this->menu->add('burgers-and-sandwiches', 'Club Sandwich', 'Chicken, egg, cheese and vegetables.', [['Regular', 350, null]], [...$grilled, 'contains-dairy']);
        $this->menu->add('burgers-and-sandwiches', 'Chicken Sub', 'Grilled chicken, cheese and salad in a sub roll.', [['6 inch', 320, null], ['12 inch', 560, null]], [...$grilled, 'contains-dairy']);
    }

    private function salads(): void
    {
        $fresh = LeadTime::madeToOrder(10);

        $this->menu->add('salads', 'Caesar Salad', 'Romaine, croutons, parmesan and Caesar dressing.', [['Regular', 320, null], ['Large', 480, null]], ['contains-dairy', 'contains-gluten'], $fresh);
        $this->menu->add('salads', 'Greek Salad', 'Tomato, cucumber, olives and feta.', [['Regular', 300, null], ['Large', 450, null]], ['veg', 'contains-dairy'], $fresh);
        $this->menu->add('salads', 'Garden Salad', 'Mixed leaves and seasonal vegetables.', [['Regular', 250, null], ['Large', 380, null]], ['veg'], $fresh);
    }

    private function desserts(): void
    {
        $ready = LeadTime::madeToOrder(5);
        $sweet = ['veg', 'contains-dairy', 'contains-gluten'];

        $this->menu->add('desserts', 'Chocolate Brownie', 'Fudgy brownie with walnuts.', [['1 pc', 180, null], ['4 pcs', 650, null]], [...$sweet, 'contains-nuts'], $ready);
        $this->menu->add('desserts', 'Chocolate Lava Cake', 'Warm cake with a molten centre.', [['1 pc', 280, null]], $sweet, LeadTime::madeToOrder(15), isHot: true);
        $this->menu->add('desserts', 'New York Cheesecake', 'Creamy baked cheesecake.', [['Slice', 350, null]], $sweet, $ready);
        // Whole cakes need notice, so they are their own products with their own lead time.
        $this->menu->add('desserts', 'Celebration Cheesecake', 'A whole cheesecake for 8.', [['Whole (8 slices)', 2400, null]], $sweet, LeadTime::sameDay(180));
        $this->menu->add('desserts', 'Tiramisu', 'Coffee-soaked sponge and mascarpone.', [['Cup', 380, null]], $sweet, $ready);
        $this->menu->add('desserts', 'Tiramisu Tray', 'A family tray of tiramisu.', [['Whole tray', 2600, null]], $sweet, LeadTime::nextDay(1440));
        $this->menu->add('desserts', 'Cookies', 'Chocolate-chip cookies, baked today.', [['3 pcs', 180, null], ['6 pcs', 340, null], ['12 pcs', 650, null]], $sweet, $ready);
        $this->menu->add('desserts', 'Ice Cream', 'Vanilla, chocolate or strawberry.', [['1 scoop', 120, null], ['2 scoops', 220, null], ['3 scoops', 300, null]], ['veg', 'contains-dairy'], $ready);
        $this->menu->add('desserts', 'Cinnamon Sticks', 'Sweet dough sticks with cinnamon sugar.', [['8 pcs', 260, null]], $sweet, LeadTime::madeToOrder(15));
        $this->menu->add('desserts', 'Dessert Pizza', 'Chocolate, banana and marshmallow on a sweet base.', $this->menu->sized(450, 790, 1150), $sweet);
    }

    private function drinks(): void
    {
        $quick = LeadTime::madeToOrder(5);

        foreach (['Coca-Cola', 'Pepsi', 'Sprite', 'Fanta', '7Up'] as $softDrink) {
            $this->menu->add('soft-drinks', $softDrink, 'Chilled soft drink.', [['250 ml', 50, null], ['500 ml', 70, null], ['1.5 L', 130, null]], ['veg'], $quick);
        }

        foreach (['Chocolate', 'Vanilla', 'Strawberry'] as $flavour) {
            $this->menu->add('milkshakes', "{$flavour} Milkshake", "Thick {$flavour} milkshake.", [['Regular', 280, null], ['Large', 360, null]], ['veg', 'contains-dairy'], $quick);
        }

        $this->menu->add('coffee', 'Cold Coffee', 'Iced coffee with milk.', [['Regular', 220, null], ['Large', 290, null]], ['veg', 'contains-dairy'], $quick, isNew: true);
        $this->menu->add('coffee', 'Cappuccino', 'Espresso with steamed milk foam.', [['Regular', 220, null]], ['veg', 'contains-dairy'], $quick);
        $this->menu->add('coffee', 'Caffe Latte', 'Espresso with plenty of steamed milk.', [['Regular', 230, null]], ['veg', 'contains-dairy'], $quick);

        $this->menu->add('juice-and-water', 'Fresh Orange Juice', 'Freshly squeezed oranges.', [['Regular', 220, null]], ['veg'], $quick);
        $this->menu->add('juice-and-water', 'Mineral Water', 'Still mineral water.', [['500 ml', 25, null], ['1.5 L', 50, null]], ['veg'], $quick);
    }
}
