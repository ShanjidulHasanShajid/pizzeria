<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A published, available standard product. After creating it, configure() makes sure it has
 * exactly one default variant (and one main image if it has images), so the domain can load it.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'type' => ProductType::Standard,
            'name' => ucwords(fake()->words(2, true)),
            'slug' => fake()->unique()->slug(3),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'lead_time_class' => LeadTimeClass::MadeToOrder,
            'lead_time_minutes' => 20,
            'is_new' => false,
            'is_hot' => false,
            'is_giftable' => false,
            'is_published' => true,
            'is_available' => true,
            'sort' => fake()->numberBetween(1, 50) * 10,
        ];
    }

    /**
     * Runs after the product and any ->has(...) children are saved.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            $variants = $product->variants()->orderBy('sort')->orderBy('id')->get();

            if ($variants->isEmpty()) {
                ProductVariant::factory()->for($product)->asDefault()->create();
            } elseif (! $variants->contains('is_default', true)) {
                $variants->first()->update(['is_default' => true]);
            }

            $images = $product->images()->orderBy('sort')->orderBy('id')->get();

            if ($images->isNotEmpty() && ! $images->contains('is_main', true)) {
                $images->first()->update(['is_main' => true]);
            }
        });
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }

    public function hot(): static
    {
        return $this->state(fn (): array => ['is_hot' => true]);
    }

    public function markedNew(): static
    {
        return $this->state(fn (): array => ['is_new' => true]);
    }

    /**
     * A combo has exactly one variant: configure() adds it. Do not add more.
     */
    public function combo(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::Combo]);
    }

    public function giftBox(): static
    {
        return $this->combo()->state(fn (): array => ['is_giftable' => true]);
    }
}
