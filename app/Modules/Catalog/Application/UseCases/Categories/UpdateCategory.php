<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\UpdateCategoryInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Exceptions\InvalidCategoryHierarchy;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use Throwable;

/**
 * Saves the category edit form: texts, slug, parent, station, switches and image.
 *
 * A new parent puts the category at the end of the new group. A new image replaces the
 * old one: the new files are stored first, the row is saved, and only then are the
 * old files deleted. A failed save deletes the new files and keeps the old ones.
 */
final class UpdateCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly KitchenStationRepository $stations,
        private readonly CatalogImages $images,
    ) {}

    public function handle(UpdateCategoryInput $input): void
    {
        $category = $this->categories->find($input->id)
            ?? throw EntityNotFound::withId('Category', $input->id);

        $this->moveIfNeeded($category, $input->parentId);

        if ($this->stations->find($input->kitchenStationId) === null) {
            throw ValidationFailed::forField('kitchen_station_id', 'Choose an existing kitchen station.');
        }

        $category->assignKitchenStation($input->kitchenStationId);
        $this->changeSlugIfTyped($category, $input);
        $category->rename($input->name);
        $category->describe($input->description);
        $this->applySwitches($category, $input);

        $oldImage = $category->image();
        $newImage = $input->newImage === null ? null : $this->images->store($input->newImage, CreateCategory::IMAGE_DIRECTORY);

        if ($newImage !== null) {
            $category->changeImage($newImage);
        } elseif ($input->removeImage) {
            $category->changeImage(null);
        }

        try {
            $this->categories->save($category);
        } catch (Throwable $failure) {
            if ($newImage !== null) {
                $this->images->delete($newImage);
            }

            throw $failure;
        }

        if ($oldImage !== null && ($newImage !== null || $input->removeImage)) {
            $this->images->delete($oldImage);
        }
    }

    private function moveIfNeeded(Category $category, ?int $parentId): void
    {
        if ($parentId === $category->parentId()) {
            return;
        }

        if ($parentId === null) {
            $category->moveToTopLevel();
            $category->moveTo($this->categories->nextSortOrder(null));

            return;
        }

        $parent = $this->categories->find($parentId)
            ?? throw ValidationFailed::forField('parent_id', 'Choose an existing top-level category.');

        try {
            // A loaded category always has an id; "?? 0" only satisfies the ?int type.
            $category->moveUnder($parent, hasSubCategories: $this->categories->hasChildren($category->id() ?? 0));
        } catch (InvalidCategoryHierarchy $hierarchy) {
            throw ValidationFailed::forField('parent_id', $hierarchy->getMessage());
        }

        $category->moveTo($this->categories->nextSortOrder($parentId));
    }

    private function changeSlugIfTyped(Category $category, UpdateCategoryInput $input): void
    {
        $typed = trim($input->slug ?? '');

        if ($typed === '') {
            return;
        }

        $slug = Slug::fromString($typed);

        if ($this->categories->slugExists($slug, ignoreId: $input->id)) {
            throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a category in the Trash). Choose another one.');
        }

        $category->changeSlug($slug);
    }

    private function applySwitches(Category $category, UpdateCategoryInput $input): void
    {
        if ($input->isActive) {
            $category->activate();
        } else {
            $category->deactivate();
        }

        if ($input->isFeatured) {
            $category->feature();
        } else {
            $category->unfeature();
        }

        if ($input->showInMegaMenu) {
            $category->showInMegaMenu();
        } else {
            $category->hideFromMegaMenu();
        }
    }
}
