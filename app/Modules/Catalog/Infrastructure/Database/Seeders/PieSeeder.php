<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Infrastructure\Database\Seeders\Support\MenuItemWriter;
use Illuminate\Database\Seeder;

/**
 * Pies use the same size tiers as pizzas (decision 9).
 */
final class PieSeeder extends Seeder
{
    public function __construct(private readonly MenuItemWriter $menu) {}

    public function run(): void
    {
        $pastry = ['contains-gluten', 'contains-dairy'];
        $baked = LeadTime::madeToOrder(30);

        $this->menu->add('pies', 'Chicken Pot Pie', 'Creamy chicken and vegetables under a puff-pastry lid.', $this->menu->sized(420, 780, 1150), ['non-veg', 'halal', ...$pastry], $baked);
        $this->menu->add('pies', 'Pizza Pie', 'Deep-dish pie filled with pizza toppings and cheese.', $this->menu->sized(480, 880, 1290), ['non-veg', 'halal', ...$pastry], $baked);
        $this->menu->add('pies', 'Chicken Pie', 'Spiced chicken in a golden shortcrust.', $this->menu->sized(380, 720, 1050), ['non-veg', 'halal', ...$pastry], $baked);
        // Baked in batches in the morning: order the same day.
        $this->menu->add('pies', 'Beef Pie', 'Slow-cooked beef in rich gravy.', $this->menu->sized(420, 790, 1150), ['non-veg', 'halal', ...$pastry], LeadTime::sameDay(180));
        $this->menu->add('pies', 'Mushroom Pie', 'Mushrooms, garlic and herbs in a creamy sauce.', $this->menu->sized(350, 650, 950), ['veg', ...$pastry], $baked);
    }
}
