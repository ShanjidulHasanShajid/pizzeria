<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\SitemapEntry;
use App\Modules\Catalog\Application\Queries\SitemapQuery;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;

final class EloquentSitemapQuery implements SitemapQuery
{
    public function __construct(private readonly StorefrontReader $reader) {}

    public function entries(): array
    {
        $entries = [];
        $categories = $this->reader->onlyVisible(CategoryModel::query())->with('parent')->orderBy('sort')->orderBy('id')->get();

        foreach ($categories as $category) {
            $entries[] = new SitemapEntry('category', $category->slug, $category->parent?->slug, $category->updated_at?->toDateTimeImmutable());
        }

        $products = $this->reader->visibleProducts()->orderBy('sort')->orderBy('id')->get(['id', 'slug', 'updated_at']);

        foreach ($products as $product) {
            $entries[] = new SitemapEntry('product', $product->slug, null, $product->updated_at?->toDateTimeImmutable());
        }

        return $entries;
    }
}
