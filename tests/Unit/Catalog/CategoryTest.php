<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Exceptions\InvalidCategoryHierarchy;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A category as it would come back from the database.
 */
function savedCategory(int $id, string $name, ?int $parentId = null): Category
{
    return Category::reconstitute(
        id: $id,
        name: $name,
        slug: Slug::fromString(strtolower($name)),
        description: null,
        image: null,
        parentId: $parentId,
        kitchenStationId: 1,
        sort: SortOrder::first(),
        isActive: true,
        isFeatured: false,
        showInMegaMenu: true,
    );
}

it('creates a top-level category with sensible defaults', function (): void {
    $category = Category::create('Pizza', Slug::fromString('pizza'), 1, SortOrder::first());

    expect($category->id())->toBeNull()
        ->and($category->isTopLevel())->toBeTrue()
        ->and($category->isActive())->toBeTrue()
        ->and($category->isFeatured())->toBeFalse()
        ->and($category->isShownInMegaMenu())->toBeTrue()
        ->and($category->description())->toBeNull()
        ->and($category->image())->toBeNull();
});

it('creates a sub-category under a saved top-level category', function (): void {
    $drinks = savedCategory(8, 'Drinks');

    $milkshakes = Category::create('Milkshakes', Slug::fromString('milkshakes'), 7, SortOrder::first(), parent: $drinks);

    expect($milkshakes->parentId())->toBe(8)
        ->and($milkshakes->isTopLevel())->toBeFalse();
});

it('refuses a sub-category as parent', function (): void {
    $milkshakes = savedCategory(9, 'Milkshakes', parentId: 8);

    expect(fn () => Category::create('Thick', Slug::fromString('thick'), 7, SortOrder::first(), parent: $milkshakes))
        ->toThrow(InvalidCategoryHierarchy::class);
});

it('refuses a parent that is not saved yet', function (): void {
    $unsaved = Category::create('Drinks', Slug::fromString('drinks'), 7, SortOrder::first());

    expect(fn () => Category::create('Juice', Slug::fromString('juice'), 7, SortOrder::first(), parent: $unsaved))
        ->toThrow(InvalidCategoryHierarchy::class);
});

it('moves a category under another top-level category and back', function (): void {
    $sides = savedCategory(3, 'Sides');
    $wings = savedCategory(12, 'Wings');

    $wings->moveUnder($sides, hasSubCategories: false);
    expect($wings->parentId())->toBe(3);

    $wings->moveToTopLevel();
    expect($wings->isTopLevel())->toBeTrue();
});

it('refuses to move a category that has sub-categories', function (): void {
    $drinks = savedCategory(8, 'Drinks');
    $sides = savedCategory(3, 'Sides');

    expect(fn () => $drinks->moveUnder($sides, hasSubCategories: true))
        ->toThrow(InvalidCategoryHierarchy::class);
});

it('refuses to make a category its own parent', function (): void {
    $drinks = savedCategory(8, 'Drinks');

    expect(fn () => $drinks->moveUnder($drinks, hasSubCategories: false))
        ->toThrow(InvalidCategoryHierarchy::class, 'own parent');
});

it('stores an empty description as null and trims the rest', function (): void {
    $category = savedCategory(1, 'Pizza');

    $category->describe('   ');
    expect($category->description())->toBeNull();

    $category->describe('  Stone-baked.  ');
    expect($category->description())->toBe('Stone-baked.');
});

it('changes image, station and the featured and mega menu switches', function (): void {
    $category = savedCategory(1, 'Pizza');

    $category->changeImage(ImagePath::of('categories/pizza.webp'));
    $category->assignKitchenStation(2);
    $category->feature();
    $category->hideFromMegaMenu();

    expect($category->image()?->original())->toBe('categories/pizza.webp')
        ->and($category->kitchenStationId())->toBe(2)
        ->and($category->isFeatured())->toBeTrue()
        ->and($category->isShownInMegaMenu())->toBeFalse();
});

it('requires a kitchen station', function (): void {
    expect(fn () => Category::create('Pizza', Slug::fromString('pizza'), 0, SortOrder::first()))
        ->toThrow(InvalidValue::class);
});

it('rejects an empty name', function (): void {
    expect(fn () => Category::create('', Slug::fromString('pizza'), 1, SortOrder::first()))
        ->toThrow(InvalidValue::class);
});
