<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Mappers;

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Translates between a categories row (Eloquent) and the Category entity (domain).
 */
final class CategoryMapper
{
    public function toEntity(CategoryModel $model): Category
    {
        return Category::reconstitute(
            id: $model->id,
            name: $model->name,
            slug: Slug::fromString($model->slug),
            description: $model->description,
            image: $model->image_path === null ? null : ImagePath::of($model->image_path, $model->image_thumbnail_path),
            parentId: $model->parent_id,
            kitchenStationId: $model->kitchen_station_id,
            sort: SortOrder::fromInt($model->sort),
            isActive: $model->is_active,
            isFeatured: $model->is_featured,
            showInMegaMenu: $model->show_in_mega_menu,
        );
    }

    /**
     * The column values for fill().
     *
     * @return array<string, mixed>
     */
    public function toAttributes(Category $category): array
    {
        $image = $category->image();

        return [
            'parent_id' => $category->parentId(),
            'kitchen_station_id' => $category->kitchenStationId(),
            'name' => $category->name(),
            'slug' => $category->slug()->value(),
            'description' => $category->description(),
            'image_path' => $image?->original(),
            'image_thumbnail_path' => $image !== null && $image->hasThumbnail() ? $image->thumbnail() : null,
            'sort' => $category->sort()->value(),
            'is_active' => $category->isActive(),
            'is_featured' => $category->isFeatured(),
            'show_in_mega_menu' => $category->isShownInMegaMenu(),
        ];
    }
}
