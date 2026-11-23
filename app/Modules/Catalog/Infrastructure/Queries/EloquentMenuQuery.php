<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\CategoryPage;
use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Catalog\Application\DTOs\CategorySectionRequest;
use App\Modules\Catalog\Application\DTOs\MenuFilter;
use App\Modules\Catalog\Application\DTOs\ProductCardPage;
use App\Modules\Catalog\Application\DTOs\TagBadge;
use App\Modules\Catalog\Application\Queries\MenuQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Domain\Enums\ProductSort;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class EloquentMenuQuery implements MenuQuery
{
    public function __construct(
        private readonly StorefrontReader $reader,
        private readonly StorefrontCatalogQuery $catalog,
    ) {}

    public function categories(): array
    {
        return $this->reader->categoryNodes(CategoryModel::query());
    }

    public function sections(): array
    {
        $requests = array_map(
            fn (CategoryNode $category): CategorySectionRequest => new CategorySectionRequest($category->id, PHP_INT_MAX),
            $this->categories(),
        );

        $sections = [];

        foreach ($this->catalog->categorySections($requests) as $section) {
            if ($section !== null && $section->products !== []) {
                $sections[] = $section;
            }
        }

        /** @var list<CategorySection> $sections */
        return $sections;
    }

    public function category(string $slug): ?CategoryPage
    {
        $category = $this->reader->onlyVisible(CategoryModel::query())
            ->where('slug', $slug)
            ->with(['parent', 'children'])
            ->first();

        return $category === null ? null : new CategoryPage(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            description: $category->description,
            parentName: $category->parent?->name,
            parentSlug: $category->parent?->slug,
            children: $this->reader->activeChildren($category),
        );
    }

    public function tag(string $slug): ?TagBadge
    {
        $tag = TagModel::query()->where('is_active', true)->where('slug', $slug)->first();

        return $tag === null ? null : $this->reader->toBadge($tag);
    }

    public function filterTags(): array
    {
        $tags = TagModel::query()
            ->where('is_active', true)
            ->where('type', '!=', TagType::Allergen->value)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($tags->map(fn (TagModel $tag): TagBadge => $this->reader->toBadge($tag))->all());
    }

    public function listing(MenuFilter $filter): ProductCardPage
    {
        $query = $this->filtered($filter);
        $total = $query->count();
        $page = min(max(1, $filter->page), max(1, (int) ceil($total / $filter->perPage)));

        $this->sort($query, $filter->sort);

        return new ProductCardPage(
            $this->reader->load($query->forPage($page, $filter->perPage)),
            $total,
            $page,
            $filter->perPage,
        );
    }

    /**
     * @return Builder<ProductModel>
     */
    private function filtered(MenuFilter $filter): Builder
    {
        $query = $this->reader->visibleProducts();

        if ($filter->categoryId !== null) {
            $query->whereIn('category_id', CategoryModel::query()
                ->select('id')
                ->where(fn (Builder $family) => $family->whereKey($filter->categoryId)->orWhere('parent_id', $filter->categoryId)));
        }

        $tags = $filter->pageTag === null ? $filter->tagSlugs : [$filter->pageTag, ...$filter->tagSlugs];

        foreach (array_unique($tags) as $slug) {
            $query->whereHas('tags', fn (Builder $tag) => $tag->where('slug', $slug)->where('is_active', true));
        }

        if ($filter->minPrice !== null) {
            $query->where($this->cardPrice(), '>=', $filter->minPrice->poisha());
        }

        if ($filter->maxPrice !== null) {
            $query->where($this->cardPrice(), '<=', $filter->maxPrice->poisha());
        }

        if ($filter->onlyAvailable) {
            $query->where('is_available', true)
                ->whereHas('variants', fn (Builder $variant) => $variant->where('is_available', true));
        }

        return $query;
    }

    /**
     * @param  Builder<ProductModel>  $query
     */
    private function sort(Builder $query, ProductSort $sort): void
    {
        match ($sort) {
            ProductSort::Recommended => $query->orderBy('sort'),
            ProductSort::Newest => $query->orderByDesc('created_at')->orderByDesc('id'),
            ProductSort::PriceLowToHigh => $query->orderBy($this->cardPrice())->orderBy('sort'),
            ProductSort::PriceHighToLow => $query->orderByDesc($this->cardPrice())->orderBy('sort'),
        };

        $query->orderBy('id'); // never two products in a random order
    }

    /**
     * The price on the card, as a sub-query: the cheapest available variant, otherwise the cheapest one.
     *
     * @return Closure(QueryBuilder): mixed
     */
    private function cardPrice(): Closure
    {
        return fn (QueryBuilder $variants): QueryBuilder => $variants
            ->select('price')
            ->from('product_variants')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->whereNull('product_variants.deleted_at')
            ->orderByDesc('is_available')
            ->orderBy('price')
            ->limit(1);
    }
}
