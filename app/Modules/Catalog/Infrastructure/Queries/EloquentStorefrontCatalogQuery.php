<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Catalog\Application\DTOs\CategorySectionRequest;
use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\ProductDetail;
use App\Modules\Catalog\Application\DTOs\ProductImageView;
use App\Modules\Catalog\Application\DTOs\ProductPricingFacts;
use App\Modules\Catalog\Application\DTOs\VariantPricingFacts;
use App\Modules\Catalog\Application\DTOs\VariantView;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection;

/**
 * The shop pages read the catalog here. Every product query starts from visibleProducts() of the
 * StorefrontReader, so the visibility rule lives in exactly one place.
 */
final class EloquentStorefrontCatalogQuery implements StorefrontCatalogQuery
{
    public function __construct(private readonly StorefrontReader $reader) {}

    public function productCardsForCategory(int $categoryId): array
    {
        return $this->reader->cards(
            $this->reader->visibleProducts()->whereIn('category_id', $this->categoryAndChildren($categoryId)),
        );
    }

    public function hotProducts(int $limit): array
    {
        return $this->reader->cards($this->reader->visibleProducts()->where('is_hot', true), $limit);
    }

    public function showcaseProducts(int $categoryId, int $limit): array
    {
        return $this->reader->cards(
            $this->reader->visibleProducts()->whereIn('category_id', $this->categoryAndChildren($categoryId)),
            $limit,
        );
    }

    public function categorySections(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $categories = $this->reader->onlyVisible(CategoryModel::query())
            ->whereKey(array_map(fn (CategorySectionRequest $request): int => $request->categoryId, $requests))
            ->with('children')
            ->get()
            ->keyBy('id');

        $chosenIds = $this->chosenProductIds($requests, $categories);
        $allIds = array_values(array_unique(array_merge(...$chosenIds)));
        $cards = [];

        foreach ($allIds === [] ? [] : $this->reader->cards($this->reader->visibleProducts()->whereKey($allIds)) as $card) {
            $cards[$card->id] = $card;
        }

        $sections = [];

        foreach ($requests as $position => $request) {
            $category = $categories->get($request->categoryId);

            $sections[] = $category === null ? null : new CategorySection(
                id: $category->id,
                name: $category->name,
                slug: $category->slug,
                products: array_values(array_filter(array_map(fn (int $id): ?ProductCard => $cards[$id] ?? null, $chosenIds[$position]))),
            );
        }

        return $sections;
    }

    public function productDetail(string $slug): ?ProductDetail
    {
        $product = $this->reader->visibleProducts()
            ->where('slug', $slug)
            ->with(['category', 'variants.sizeTier', 'images', 'tags'])
            ->first();

        if ($product === null) {
            return null;
        }

        $leadTime = LeadTime::of($product->lead_time_class, $product->lead_time_minutes);
        $variants = $product->variants->sortBy('sort')->values();
        $images = $product->images->sortBy('sort')->values();

        return new ProductDetail(
            id: $product->id,
            slug: $product->slug,
            name: $product->name,
            type: $product->type,
            shortDescription: $product->short_description,
            description: $product->description,
            categoryId: $product->category_id,
            categoryName: $product->category->name,
            categorySlug: $product->category->slug,
            leadTimeLabel: $leadTime->label(),
            leadTimeClass: $product->lead_time_class,
            isNew: $product->is_new,
            isHot: $product->is_hot,
            isGiftable: $product->is_giftable,
            isAvailable: $product->is_available && $variants->contains('is_available', true),
            variants: array_values($variants->map(fn (ProductVariantModel $variant): VariantView => new VariantView(
                id: $variant->id,
                label: $variant->label,
                price: Money::fromPoisha($variant->price),
                sizeTierId: $variant->size_tier_id,
                sizeTierName: $variant->sizeTier?->name,
                isDefault: $variant->is_default,
                isAvailable: $variant->is_available,
            ))->all()),
            images: array_values($images->map(fn (ProductImageModel $image): ProductImageView => new ProductImageView(
                originalPath: $image->path,
                thumbnailPath: $image->thumbnail_path ?? $image->path,
                isMain: $image->is_main,
            ))->all()),
            tags: $this->reader->tagBadges($product),
        );
    }

    public function relatedProducts(int $productId, int $limit): array
    {
        $categoryId = ProductModel::query()->whereKey($productId)->value('category_id');

        if ($categoryId === null) {
            return [];
        }

        return $this->reader->cards(
            $this->reader->visibleProducts()->where('category_id', $categoryId)->whereKeyNot($productId),
            $limit,
        );
    }

    public function megaMenu(): array
    {
        return $this->reader->categoryNodes(CategoryModel::query()->where('show_in_mega_menu', true));
    }

    public function search(string $term, int $limit): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $query = $this->reader->matching($this->reader->visibleProducts(), $term);

        return $this->reader->cards($this->reader->nameMatchesFirst($query, $term), $limit);
    }

    public function pricingFacts(int $productId): ?ProductPricingFacts
    {
        $product = ProductModel::query()->with('variants')->find($productId);

        if ($product === null) {
            return null;
        }

        return new ProductPricingFacts(
            productId: $product->id,
            name: $product->name,
            isVisible: $this->reader->visibleProducts()->whereKey($productId)->exists(),
            isAvailable: $product->is_available,
            variants: array_values($product->variants->sortBy('sort')->map(fn (ProductVariantModel $variant): VariantPricingFacts => new VariantPricingFacts(
                id: $variant->id,
                label: $variant->label,
                price: Money::fromPoisha($variant->price),
                sizeTierId: $variant->size_tier_id,
                isAvailable: $variant->is_available,
            ))->all()),
        );
    }

    /**
     * For every request the ids of the products to show. One light query for all categories
     * (only ids, in sort order); the cards are loaded later, and only for the chosen ones.
     *
     * @param  list<CategorySectionRequest>  $requests
     * @param  Collection<int, CategoryModel>  $categories  by id, with their children
     * @return list<list<int>>
     */
    private function chosenProductIds(array $requests, Collection $categories): array
    {
        $familyOf = fn (CategoryModel $category): array => [$category->id, ...$category->children->modelKeys()];
        $candidates = $this->reader->visibleProducts()
            ->whereIn('category_id', $categories->flatMap($familyOf)->unique()->values()->all())
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'category_id']);

        $chosen = [];

        foreach ($requests as $request) {
            $category = $categories->get($request->categoryId);
            $available = [];

            if ($category !== null) {
                foreach ($candidates->whereIn('category_id', $familyOf($category)) as $product) {
                    $available[] = $product->id;
                }
            }

            $picked = array_intersect($request->pickedProductIds, $available);
            $chosen[] = array_slice(array_values(array_unique([...$picked, ...$available])), 0, $request->limit);
        }

        return $chosen;
    }

    /**
     * @return list<int> the category itself plus its sub-categories
     */
    private function categoryAndChildren(int $categoryId): array
    {
        return array_values(CategoryModel::query()
            ->whereKey($categoryId)
            ->orWhere('parent_id', $categoryId)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }
}
