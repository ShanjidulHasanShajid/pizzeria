<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KitchenStation>
 */
class KitchenStationFactory extends Factory
{
    protected $model = KitchenStation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->word()).' Station',
            'sort' => fake()->numberBetween(1, 20) * 10,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
