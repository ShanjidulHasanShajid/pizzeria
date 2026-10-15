<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SizeTier>
 */
class SizeTierFactory extends Factory
{
    protected $model = SizeTier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->word()),
            // unique(): never the same slug twice in one run (slugs are unique in the table).
            'slug' => fake()->unique()->slug(2),
            'sort' => fake()->numberBetween(1, 20) * 10,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
