<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Mappers;

use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Entities\ProductImage;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Translates between the products rows (product, variants, images, tag links)
 * and the Product aggregate.
 */
final class ProductMapper
{
    /**
     * The model must come with variants, images and tagsIncludingDeleted loaded.
     * Product::reconstitute() checks every rule, so a broken row fails here, loudly.
     */
    public function toEntity(ProductModel $model): Product
    {
        return Product::reconstitute(
            id: $model->id,
            type: $model->type,
            categoryId: $model->category_id,
            name: $model->name,
            slug: Slug::fromString($model->slug),
            shortDescription: $model->short_description,
            description: $model->description,
            leadTime: LeadTime::of($model->lead_time_class, $model->lead_time_minutes),
            badges: ProductBadges::of($model->is_new, $model->is_hot),
            isGiftable: $model->is_giftable,
            isPublished: $model->is_published,
            isAvailable: $model->is_available,
            sort: SortOrder::fromInt($model->sort),
            variants: array_values($model->variants->map(fn (ProductVariantModel $variant): Variant => $this->toVariant($variant))->all()),
            images: array_values($model->images->map(fn (ProductImageModel $image): ProductImage => $this->toImage($image))->all()),
            tagIds: array_values($model->tagsIncludingDeleted->map(fn (TagModel $tag): int => $tag->id)->all()),
        );
    }

    /**
     * The products columns for fill().
     *
     * @return array<string, mixed>
     */
    public function toAttributes(Product $product): array
    {
        return [
            'category_id' => $product->categoryId(),
            'type' => $product->type(),
            'name' => $product->name(),
            'slug' => $product->slug()->value(),
            'short_description' => $product->shortDescription(),
            'description' => $product->description(),
            'lead_time_class' => $product->leadTime()->leadTimeClass(),
            'lead_time_minutes' => $product->leadTime()->minutes(),
            'is_new' => $product->badges()->isNew(),
            'is_hot' => $product->badges()->isHot(),
            'is_giftable' => $product->isGiftable(),
            'is_published' => $product->isPublished(),
            'is_available' => $product->isAvailable(),
            'sort' => $product->sort()->value(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function variantAttributes(Variant $variant): array
    {
        return [
            'size_tier_id' => $variant->sizeTierId(),
            'label' => $variant->label(),
            'price' => $variant->price()->poisha(),
            'is_default' => $variant->isDefault(),
            'is_available' => $variant->isAvailable(),
            'sort' => $variant->sort()->value(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function imageAttributes(ProductImage $image): array
    {
        $path = $image->path();

        return [
            'path' => $path->original(),
            'thumbnail_path' => $path->hasThumbnail() ? $path->thumbnail() : null,
            'is_main' => $image->isMain(),
            'sort' => $image->sort()->value(),
        ];
    }

    private function toVariant(ProductVariantModel $model): Variant
    {
        return Variant::reconstitute(
            $model->id,
            $model->label,
            Money::fromPoisha($model->price),
            $model->size_tier_id,
            $model->is_default,
            $model->is_available,
            SortOrder::fromInt($model->sort),
        );
    }

    private function toImage(ProductImageModel $model): ProductImage
    {
        return ProductImage::reconstitute(
            $model->id,
            ImagePath::of($model->path, $model->thumbnail_path),
            $model->is_main,
            SortOrder::fromInt($model->sort),
        );
    }
}
