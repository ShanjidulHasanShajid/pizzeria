<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The 9 menu categories, plus sub-categories under Drinks.
 */
final class CategorySeeder extends Seeder
{
    /** slug => [name, kitchen station, description, featured on the home page] */
    private const TOP_LEVEL = [
        'pizza' => ['Pizza', 'Oven', 'Stone-baked pizzas in three sizes.', true],
        'pies' => ['Pies', 'Oven', 'Golden pies, baked fresh.', false],
        'sides' => ['Sides', 'Fryer', 'Garlic bread, wings and crispy bites.', true],
        'pasta' => ['Pasta', 'Pasta', 'Creamy, saucy and baked pasta.', false],
        'burgers-and-sandwiches' => ['Burgers & Sandwiches', 'Grill', 'Grilled burgers and toasted sandwiches.', false],
        'salads' => ['Salads', 'Cold Prep', 'Fresh salads with house dressings.', false],
        'desserts' => ['Desserts', 'Desserts', 'Cakes, cookies and something sweet.', true],
        'drinks' => ['Drinks', 'Drinks', 'Soft drinks, shakes, coffee and juice.', false],
        'meal-deals' => ['Meal Deals', 'Oven', 'Combos for one, two or the whole family.', false],
    ];

    /** slug => name; all of them under Drinks */
    private const DRINK_TYPES = [
        'soft-drinks' => 'Soft Drinks',
        'milkshakes' => 'Milkshakes',
        'coffee' => 'Coffee',
        'juice-and-water' => 'Juice & Water',
    ];

    public function __construct(private readonly CategoryRepository $categories) {}

    public function run(): void
    {
        $stationIds = KitchenStationModel::query()->pluck('id', 'name')->all();
        $sort = SortOrder::first();
        $drinksId = null;

        foreach (self::TOP_LEVEL as $slug => [$name, $station, $description, $featured]) {
            $category = Category::create($name, Slug::fromString($slug), (int) ($stationIds[$station] ?? 0), $sort, description: $description);

            if ($featured) {
                $category->feature();
            }

            // Combos need their slots first (Phase 15). Until then the category stays out of the menu.
            if ($slug === 'meal-deals') {
                $category->hideFromMegaMenu();
            }

            $id = $this->categories->save($category);
            $sort = $sort->next();

            if ($slug === 'drinks') {
                $drinksId = $id;
            }
        }

        $drinks = $this->categories->find((int) $drinksId) ?? throw new RuntimeException('The Drinks category was not saved.');
        $sort = SortOrder::first();

        foreach (self::DRINK_TYPES as $slug => $name) {
            $this->categories->save(Category::create($name, Slug::fromString($slug), $drinks->kitchenStationId(), $sort, parent: $drinks));
            $sort = $sort->next();
        }
    }
}
