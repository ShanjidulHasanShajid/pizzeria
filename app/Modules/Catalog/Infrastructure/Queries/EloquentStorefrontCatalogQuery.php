<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\ProductDetail;
use App\Modules\Catalog\Application\DTOs\ProductImageView;
use App\Modules\Catalog\Application\DTOs\TagBadge;
use App\Modules\Catalog\Application\DTOs\VariantView;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;

/**
 * The shop pages read the catalog here. Every product query starts from visibleProducts(),
 * so the visibility rule lives in exactly one place.
 */
final class EloquentStorefrontCatalogQuery implements StorefrontCatalogQuery
{
    public function productCardsForCategory(int $categoryId): array
    {
        return $this->cards(
            $this->visibleProducts()->whereIn('category_id', $this->categoryAndChildren($categoryId)),
        );
    }

    public function hotProducts(int $limit): array
    {
        return $this->cards($this->visibleProducts()->where('is_hot', true), $limit);
    }

    public function showcaseProducts(int $categoryId, int $limit): array
    {
        return $this->cards(
            $this->visibleProducts()->whereIn('category_id', $this->categoryAndChildren($categoryId)),
            $limit,
        );
    }

    public function productDetail(string $slug): ?ProductDetail
    {
        $product = $this->visibleProducts()
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
            tags: $this->tagBadges($product),
        );
    }

    public function relatedProducts(int $productId, int $limit): array
    {
        $categoryId = ProductModel::query()->whereKey($productId)->value('category_id');

        if ($categoryId === null) {
            return [];
        }

        return $this->cards(
            $this->visibleProducts()->where('category_id', $categoryId)->whereKeyNot($productId),
            $limit,
        );
    }

    public function megaMenu(): array
    {
        $categories = CategoryModel::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->where('show_in_mega_menu', true)
            ->with('children')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($categories->map(fn (CategoryModel $category): CategoryNode => $this->toNode(
            $category,
            array_values($category->children
                ->filter(fn (CategoryModel $child): bool => $child->is_active)
                ->sortBy('sort')
                ->map(fn (CategoryModel $child): CategoryNode => $this->toNode($child))
                ->all()),
        ))->all());
    }

    public function search(string $term, int $limit): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        // Escape % and _ so a search for "50%" does not act as a wildcard.
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->cards(
            $this->visibleProducts()->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like);
            }),
            $limit,
        );
    }

    /**
     * Published products that are not deleted and have at least one variant, in an active
     * category that is not deleted (for a sub-category: whose parent is active too).
     *
     * @return Builder<ProductModel>
     */
    private function visibleProducts(): Builder
    {
        return ProductModel::query()
            ->where('is_published', true)
            ->whereHas('variants')
            ->whereHas('category', function (Builder $category): void {
                $category->where('is_active', true)
                    ->where(function (Builder $topLevelOrActiveParent): void {
                        $topLevelOrActiveParent->whereNull('parent_id')
                            ->orWhereHas('parent', fn (Builder $parent) => $parent->where('is_active', true));
                    });
            });
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

    /**
     * Loads what a card needs (3 extra queries in total, not 3 per product) and builds the cards.
     *
     * @param  Builder<ProductModel>  $query
     * @return list<ProductCard>
     */
    private function cards(Builder $query, ?int $limit = null): array
    {
        $query->with(['variants', 'mainImage', 'tags'])
            ->orderBy('sort')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return array_values($query->get()->map(fn (ProductModel $product): ProductCard => $this->toCard($product))->all());
    }

    private function toCard(ProductModel $product): ProductCard
    {
        $variants = $product->variants->sortBy('sort')->values();
        $available = $variants->where('is_available', true);
        // "from" price: the cheapest available variant; when none is available, the cheapest one.
        $priced = $available->isNotEmpty() ? $available : $variants;
        $leadTime = LeadTime::of($product->lead_time_class, $product->lead_time_minutes);
        $image = $product->mainImage;

        return new ProductCard(
            id: $product->id,
            slug: $product->slug,
            name: $product->name,
            shortDescription: $product->short_description,
            type: $product->type,
            fromPrice: Money::fromPoisha((int) $priced->min('price')),
            hasSeveralPrices: $priced->pluck('price')->unique()->count() > 1,
            variantLabels: array_values($variants->map(fn (ProductVariantModel $variant): string => $variant->label)->all()),
            imagePath: $image === null ? null : ($image->thumbnail_path ?? $image->path),
            tags: $this->tagBadges($product),
            isNew: $product->is_new,
            isHot: $product->is_hot,
            leadTimeLabel: $leadTime->label(),
            leadTimeClass: $product->lead_time_class,
            isAvailable: $product->is_available && $available->isNotEmpty(),
        );
    }

    /**
     * Active tags only, in their sort order. Deleted tags are already left out by the relation.
     *
     * @return list<TagBadge>
     */
    private function tagBadges(ProductModel $product): array
    {
        return array_values($product->tags
            ->filter(fn (TagModel $tag): bool => $tag->is_active)
            ->sortBy('sort')
            ->map(fn (TagModel $tag): TagBadge => new TagBadge($tag->name, $tag->slug, $tag->type, $tag->icon))
            ->all());
    }

    /**
     * @param  list<CategoryNode>  $children
     */
    private function toNode(CategoryModel $category, array $children = []): CategoryNode
    {
        return new CategoryNode(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            imagePath: $category->image_thumbnail_path ?? $category->image_path,
            children: $children,
        );
    }
}
