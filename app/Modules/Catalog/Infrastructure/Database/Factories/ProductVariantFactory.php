<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size_tier_id' => null,
            // Labels must be unique inside a product; a unique number is the simplest way.
            'label' => 'Size '.fake()->unique()->numberBetween(1, 1_000_000),
            // Poisha: ৳100 to ৳2000 in whole taka.
            'price' => fake()->numberBetween(100, 2000) * 100,
            'is_default' => false,
            'is_available' => true,
            'sort' => fake()->numberBetween(1, 9) * 10,
        ];
    }

    public function asDefault(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }

    /**
     * Linked to a size tier and named after it ("Large").
     */
    public function forSizeTier(SizeTier $sizeTier): static
    {
        return $this->state(fn (): array => [
            'size_tier_id' => $sizeTier->id,
            'label' => $sizeTier->name,
        ]);
    }
}
