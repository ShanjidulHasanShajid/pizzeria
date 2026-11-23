<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\TagBadge;
use App\Modules\Catalog\Application\Ports\ProductOptions;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * What the storefront queries share: the visibility rules, and how cards and category nodes are
 * built. The rule "what may a customer see?" lives here and nowhere else.
 */
final class StorefrontReader
{
    public function __construct(private readonly ProductOptions $options) {}

    /**
     * Published products that are not deleted and have at least one variant, in an active
     * category that is not deleted (for a sub-category: whose parent is active too).
     *
     * @return Builder<ProductModel>
     */
    public function visibleProducts(): Builder
    {
        return ProductModel::query()
            ->where('is_published', true)
            ->whereHas('variants')
            ->whereHas('category', fn (Builder $category) => $this->onlyVisible($category));
    }

    /**
     * Active categories that are not deleted; for a sub-category the parent must be active too.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $categories
     * @return Builder<TModel>
     */
    public function onlyVisible(Builder $categories): Builder
    {
        return $categories->where('is_active', true)
            ->where(function (Builder $topLevelOrActiveParent): void {
                $topLevelOrActiveParent->whereNull('parent_id')
                    ->orWhereHas('parent', fn (Builder $parent) => $parent->where('is_active', true));
            });
    }

    /**
     * Cards in sort order.
     *
     * @param  Builder<ProductModel>  $query
     * @return list<ProductCard>
     */
    public function cards(Builder $query, ?int $limit = null): array
    {
        $query->orderBy('sort')->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $this->load($query);
    }

    /**
     * Cards in the order of the query. Loads what a card needs with 4 extra queries in total,
     * not 4 per product.
     *
     * @param  Builder<ProductModel>  $query
     * @return list<ProductCard>
     */
    public function load(Builder $query): array
    {
        $products = $query->with(['variants', 'mainImage', 'tags'])->get();
        $ids = array_values($products->map(fn (ProductModel $product): int => $product->id)->all());
        $withOptions = $ids === [] ? [] : $this->options->idsWithOptions($ids);

        return array_values($products->map(
            fn (ProductModel $product): ProductCard => $this->toCard($product, in_array($product->id, $withOptions, true)),
        )->all());
    }

    /**
     * Active tags only, in their sort order. Deleted tags are already left out by the relation.
     *
     * @return list<TagBadge>
     */
    public function tagBadges(ProductModel $product): array
    {
        return array_values($product->tags
            ->filter(fn (TagModel $tag): bool => $tag->is_active)
            ->sortBy('sort')
            ->map(fn (TagModel $tag): TagBadge => $this->toBadge($tag))
            ->all());
    }

    public function toBadge(TagModel $tag): TagBadge
    {
        return new TagBadge($tag->name, $tag->slug, $tag->type, $tag->icon);
    }

    /**
     * Top-level categories in sort order, each with its active sub-categories.
     *
     * @param  Builder<CategoryModel>  $topLevel
     * @return list<CategoryNode>
     */
    public function categoryNodes(Builder $topLevel): array
    {
        $categories = $topLevel->whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($categories->map(
            fn (CategoryModel $category): CategoryNode => $this->toNode($category, $this->activeChildren($category)),
        )->all());
    }

    /**
     * @return list<CategoryNode>
     */
    public function activeChildren(CategoryModel $category): array
    {
        return array_values($category->children
            ->filter(fn (CategoryModel $child): bool => $child->is_active)
            ->sortBy('sort')
            ->map(fn (CategoryModel $child): CategoryNode => $this->toNode($child))
            ->all());
    }

    private function toCard(ProductModel $product, bool $hasOptions): ProductCard
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
            hasOptions: $hasOptions,
        );
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
