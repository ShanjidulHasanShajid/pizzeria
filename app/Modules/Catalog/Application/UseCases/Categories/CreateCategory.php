<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\CreateCategoryInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Exceptions\InvalidCategoryHierarchy;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use Throwable;

/**
 * Adds a category (or a sub-category) at the end of its group. Returns its id.
 *
 * Order: check everything first, then store the image files, then save the row.
 * If saving fails, the new files are deleted again, so no orphan files are left.
 */
final class CreateCategory
{
    public const IMAGE_DIRECTORY = 'categories';

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly KitchenStationRepository $stations,
        private readonly SlugGenerator $slugs,
        private readonly CatalogImages $images,
    ) {}

    public function handle(CreateCategoryInput $input): int
    {
        $parent = $this->parentFor($input->parentId);

        if ($this->stations->find($input->kitchenStationId) === null) {
            throw ValidationFailed::forField('kitchen_station_id', 'Choose an existing kitchen station.');
        }

        try {
            $category = Category::create(
                name: $input->name,
                slug: $this->slugFor($input),
                kitchenStationId: $input->kitchenStationId,
                sort: $this->categories->nextSortOrder($parent?->id()),
                parent: $parent,
                description: $input->description,
            );
        } catch (InvalidCategoryHierarchy $hierarchy) {
            throw ValidationFailed::forField('parent_id', $hierarchy->getMessage());
        }

        if (! $input->isActive) {
            $category->deactivate();
        }

        if ($input->isFeatured) {
            $category->feature();
        }

        if (! $input->showInMegaMenu) {
            $category->hideFromMegaMenu();
        }

        if ($input->image === null) {
            return $this->categories->save($category);
        }

        $image = $this->images->store($input->image, self::IMAGE_DIRECTORY);
        $category->changeImage($image);

        try {
            return $this->categories->save($category);
        } catch (Throwable $failure) {
            $this->images->delete($image);

            throw $failure;
        }
    }

    private function parentFor(?int $parentId): ?Category
    {
        if ($parentId === null) {
            return null;
        }

        return $this->categories->find($parentId)
            ?? throw ValidationFailed::forField('parent_id', 'Choose an existing top-level category.');
    }

    private function slugFor(CreateCategoryInput $input): Slug
    {
        $typed = trim($input->slug ?? '');

        if ($typed === '') {
            return $this->slugs->generate($input->name, fn (Slug $slug): bool => $this->categories->slugExists($slug));
        }

        $slug = Slug::fromString($typed);

        if ($this->categories->slugExists($slug)) {
            throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a category in the Trash). Choose another one.');
        }

        return $slug;
    }
}
