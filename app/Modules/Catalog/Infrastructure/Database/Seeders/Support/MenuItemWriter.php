<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders\Support;

use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use InvalidArgumentException;

/**
 * Turns one line of menu data into a published Product aggregate and saves it.
 * Used by the product seeders only (development data).
 */
final class MenuItemWriter
{
    /** @var array<int|string, int>|null slug => id */
    private ?array $categoryIds = null;

    /** @var array<int|string, int>|null slug => id */
    private ?array $sizeTierIds = null;

    /** @var array<int|string, int>|null slug => id */
    private ?array $tagIds = null;

    /** @var array<string, SortOrder> category slug => last product position */
    private array $lastSort = [];

    public function __construct(
        private readonly ProductRepository $products,
        private readonly SlugGenerator $slugs,
    ) {}

    /**
     * Personal, Medium and Large, linked to the size tiers, prices in taka.
     *
     * @return list<array{string, int, string|null}>
     */
    public function sized(int $personal, int $medium, int $large): array
    {
        return [
            ['Personal', $personal, 'personal'],
            ['Medium', $medium, 'medium'],
            ['Large', $large, 'large'],
        ];
    }

    /**
     * @param  list<array{string, int, string|null}>  $variants  [label, price in taka, size tier slug or null]; the first is the default
     * @param  list<string>  $tagSlugs
     */
    public function add(
        string $categorySlug,
        string $name,
        string $shortDescription,
        array $variants,
        array $tagSlugs = [],
        ?LeadTime $leadTime = null,
        bool $isHot = false,
        bool $isNew = false,
    ): int {
        $product = null;
        $variantSort = SortOrder::first();

        foreach ($variants as [$label, $taka, $sizeTierSlug]) {
            $variant = Variant::create(
                $label,
                Money::fromTaka($taka),
                $variantSort,
                $sizeTierSlug === null ? null : $this->sizeTierId($sizeTierSlug),
            );
            $variantSort = $variantSort->next();

            if ($product === null) {
                $product = Product::create(
                    type: ProductType::Standard,
                    categoryId: $this->categoryId($categorySlug),
                    name: $name,
                    slug: $this->slugs->generate($name, fn (Slug $slug): bool => $this->products->slugExists($slug)),
                    leadTime: $leadTime ?? LeadTime::madeToOrder(20),
                    firstVariant: $variant,
                    sort: $this->nextSort($categorySlug),
                    shortDescription: $shortDescription,
                );

                continue;
            }

            $product->addVariant($variant);
        }

        if ($product === null) {
            throw new InvalidArgumentException("\"{$name}\" needs at least one variant.");
        }

        $product->changeBadges(ProductBadges::of($isNew, $isHot));
        $product->assignTags(array_map(fn (string $slug): int => $this->tagId($slug), $tagSlugs));
        $product->publish();

        return $this->products->save($product);
    }

    private function categoryId(string $slug): int
    {
        $this->categoryIds ??= array_map('intval', CategoryModel::query()->pluck('id', 'slug')->all());

        return $this->categoryIds[$slug] ?? throw new InvalidArgumentException("Unknown category \"{$slug}\". Run CategorySeeder first.");
    }

    private function sizeTierId(string $slug): int
    {
        $this->sizeTierIds ??= array_map('intval', SizeTierModel::query()->pluck('id', 'slug')->all());

        return $this->sizeTierIds[$slug] ?? throw new InvalidArgumentException("Unknown size \"{$slug}\". Run SizeTierSeeder first.");
    }

    private function tagId(string $slug): int
    {
        $this->tagIds ??= array_map('intval', TagModel::query()->pluck('id', 'slug')->all());

        return $this->tagIds[$slug] ?? throw new InvalidArgumentException("Unknown tag \"{$slug}\". Run TagSeeder first.");
    }

    private function nextSort(string $categorySlug): SortOrder
    {
        $this->lastSort[$categorySlug] = isset($this->lastSort[$categorySlug])
            ? $this->lastSort[$categorySlug]->next()
            : SortOrder::first();

        return $this->lastSort[$categorySlug];
    }
}
