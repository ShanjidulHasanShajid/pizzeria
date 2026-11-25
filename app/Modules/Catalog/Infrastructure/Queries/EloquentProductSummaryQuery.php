<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\ProductSummary;
use App\Modules\Catalog\Application\Queries\ProductSummaryQuery;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;

final class EloquentProductSummaryQuery implements ProductSummaryQuery
{
    public function __construct(private readonly StorefrontReader $reader) {}

    public function byIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $visibleIds = $this->reader->visibleProducts()->whereKey($ids)->pluck('id')->all();
        $summaries = [];

        foreach (ProductModel::withTrashed()->with('mainImage')->whereKey($ids)->get() as $product) {
            $image = $product->mainImage;

            $summaries[$product->id] = new ProductSummary(
                id: $product->id,
                slug: $product->slug,
                name: $product->name,
                isStandard: $product->type === ProductType::Standard,
                isVisible: in_array($product->id, $visibleIds, true),
                imagePath: $image === null ? null : ($image->thumbnail_path ?? $image->path),
            );
        }

        return $summaries;
    }
}
