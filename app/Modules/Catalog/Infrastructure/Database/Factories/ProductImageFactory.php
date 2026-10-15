<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Only the path is stored; no real file is created.
 *
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'products/'.fake()->uuid().'.webp',
            'thumbnail_path' => null,
            'is_main' => false,
            'sort' => fake()->numberBetween(1, 9) * 10,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (): array => ['is_main' => true]);
    }
}
