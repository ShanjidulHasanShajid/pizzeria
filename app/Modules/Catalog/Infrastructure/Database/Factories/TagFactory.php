<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->word()),
            'slug' => fake()->unique()->slug(2),
            'type' => fake()->randomElement(TagType::cases()),
            'icon' => null,
            'sort' => fake()->numberBetween(1, 20) * 10,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
