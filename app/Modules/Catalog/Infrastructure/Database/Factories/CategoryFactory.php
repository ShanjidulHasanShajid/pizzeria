<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            // A factory as a value: a new kitchen station is created for the category,
            // unless the test passes its own (->for($station) or ['kitchen_station_id' => ...]).
            'kitchen_station_id' => KitchenStation::factory(),
            'name' => ucwords(fake()->words(2, true)),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->sentence(),
            'image_path' => null,
            'image_thumbnail_path' => null,
            'sort' => fake()->numberBetween(1, 20) * 10,
            'is_active' => true,
            'is_featured' => false,
            'show_in_mega_menu' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function hiddenFromMegaMenu(): static
    {
        return $this->state(fn (): array => ['show_in_mega_menu' => false]);
    }

    /**
     * A sub-category of $parent, made in the parent's kitchen station.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->id,
            'kitchen_station_id' => $parent->kitchen_station_id,
        ]);
    }
}
