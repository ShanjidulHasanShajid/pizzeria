<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Modules\Catalog\Domain\Exceptions\InvalidCategoryHierarchy;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Shared\Domain\Entities\AggregateRoot;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A menu category (Pizza, Drinks) or a sub-category (Drinks -> Milkshakes).
 * Rule: only one level of sub-categories. Other categories are referenced by id only.
 */
final class Category extends AggregateRoot
{
    public const NAME_MAX_LENGTH = 100;

    public const DESCRIPTION_MAX_LENGTH = 1000;

    private function __construct(
        private ?int $id,
        private string $name,
        private Slug $slug,
        private ?string $description,
        private ?ImagePath $image,
        private ?int $parentId,
        private int $kitchenStationId,
        private SortOrder $sort,
        private bool $isActive,
        private bool $isFeatured,
        private bool $showInMegaMenu,
    ) {}

    /**
     * A new, unsaved category. Pass a parent to make it a sub-category.
     * New categories start active, not featured, and shown in the mega menu.
     */
    public static function create(
        string $name,
        Slug $slug,
        int $kitchenStationId,
        SortOrder $sort,
        ?self $parent = null,
        ?string $description = null,
        ?ImagePath $image = null,
    ): self {
        return new self(
            id: null,
            name: self::cleanName($name),
            slug: $slug,
            description: self::cleanDescription($description),
            image: $image,
            parentId: $parent === null ? null : self::validParentId($parent),
            kitchenStationId: self::validStationId($kitchenStationId),
            sort: $sort,
            isActive: true,
            isFeatured: false,
            showInMegaMenu: true,
        );
    }

    /**
     * Rebuilds a saved category from stored data. Only the mapper (Infrastructure) calls this.
     */
    public static function reconstitute(
        int $id,
        string $name,
        Slug $slug,
        ?string $description,
        ?ImagePath $image,
        ?int $parentId,
        int $kitchenStationId,
        SortOrder $sort,
        bool $isActive,
        bool $isFeatured,
        bool $showInMegaMenu,
    ): self {
        return new self(
            $id,
            self::cleanName($name),
            $slug,
            self::cleanDescription($description),
            $image,
            $parentId,
            self::validStationId($kitchenStationId),
            $sort,
            $isActive,
            $isFeatured,
            $showInMegaMenu,
        );
    }

    /**
     * Makes this category a sub-category of $parent.
     * The caller asks the repository whether this category has sub-categories of its own.
     */
    public function moveUnder(self $parent, bool $hasSubCategories): void
    {
        if ($hasSubCategories) {
            throw InvalidCategoryHierarchy::hasSubCategories($this->name);
        }

        $parentId = self::validParentId($parent);

        if ($this->id !== null && $parentId === $this->id) {
            throw InvalidCategoryHierarchy::cannotBeItsOwnParent();
        }

        $this->parentId = $parentId;
    }

    public function moveToTopLevel(): void
    {
        $this->parentId = null;
    }

    public function rename(string $name): void
    {
        $this->name = self::cleanName($name);
    }

    public function changeSlug(Slug $slug): void
    {
        $this->slug = $slug;
    }

    public function describe(?string $description): void
    {
        $this->description = self::cleanDescription($description);
    }

    /**
     * Pass null to remove the image.
     */
    public function changeImage(?ImagePath $image): void
    {
        $this->image = $image;
    }

    public function assignKitchenStation(int $kitchenStationId): void
    {
        $this->kitchenStationId = self::validStationId($kitchenStationId);
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function feature(): void
    {
        $this->isFeatured = true;
    }

    public function unfeature(): void
    {
        $this->isFeatured = false;
    }

    public function showInMegaMenu(): void
    {
        $this->showInMegaMenu = true;
    }

    public function hideFromMegaMenu(): void
    {
        $this->showInMegaMenu = false;
    }

    public function isTopLevel(): bool
    {
        return $this->parentId === null;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function image(): ?ImagePath
    {
        return $this->image;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
    }

    public function kitchenStationId(): int
    {
        return $this->kitchenStationId;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function isShownInMegaMenu(): bool
    {
        return $this->showInMegaMenu;
    }

    /**
     * The parent must be saved (so it has an id) and must itself be top level.
     */
    private static function validParentId(self $parent): int
    {
        if ($parent->id === null) {
            throw InvalidCategoryHierarchy::parentNotSaved();
        }

        if (! $parent->isTopLevel()) {
            throw InvalidCategoryHierarchy::parentIsSubCategory($parent->name);
        }

        return $parent->id;
    }

    private static function validStationId(int $kitchenStationId): int
    {
        if ($kitchenStationId < 1) {
            throw InvalidValue::because('Choose a kitchen station for the category.');
        }

        return $kitchenStationId;
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidValue::because('A category name must be 1 to 100 characters.');
        }

        return $name;
    }

    /**
     * An empty description is stored as null.
     */
    private static function cleanDescription(?string $description): ?string
    {
        $description = $description === null ? '' : trim($description);

        if ($description === '') {
            return null;
        }

        if (mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH) {
            throw InvalidValue::because('A category description may be up to 1000 characters.');
        }

        return $description;
    }
}
