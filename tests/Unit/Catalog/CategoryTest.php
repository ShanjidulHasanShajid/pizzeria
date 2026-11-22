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

describe('more rules (6.49)', function (): void {
    it('accepts a name of 100 characters and rejects 101', function (): void {
        $category = Category::create(str_repeat('x', 100), Slug::fromString('long'), 1, SortOrder::first());

        expect($category->name())->toHaveLength(100);
        expect(fn () => Category::create(str_repeat('x', 101), Slug::fromString('long'), 1, SortOrder::first()))
            ->toThrow(InvalidValue::class, '1 to 100');
    });

    it('rejects a description longer than 1000 characters', function (): void {
        expect(fn () => savedCategory(1, 'Pizza')->describe(str_repeat('x', 1001)))
            ->toThrow(InvalidValue::class, 'up to 1000');
    });

    it('keeps the description and image it was created with', function (): void {
        $category = Category::create(
            name: 'Pizza',
            slug: Slug::fromString('pizza'),
            kitchenStationId: 1,
            sort: SortOrder::first(),
            description: ' Stone-baked. ',
            image: ImagePath::of('categories/pizza.webp', 'categories/thumbs/pizza.webp'),
        );

        expect($category->description())->toBe('Stone-baked.')
            ->and($category->image()?->thumbnail())->toBe('categories/thumbs/pizza.webp');
    });

    it('refuses to move under a sub-category and stays where it was', function (): void {
        $milkshakes = savedCategory(9, 'Milkshakes', parentId: 8);
        $wings = savedCategory(12, 'Wings');

        expect(fn () => $wings->moveUnder($milkshakes, hasSubCategories: false))
            ->toThrow(InvalidCategoryHierarchy::class, 'already a sub-category');
        expect($wings->isTopLevel())->toBeTrue();
    });

    it('refuses to move under a parent that is not saved yet', function (): void {
        $unsaved = Category::create('Snacks', Slug::fromString('snacks'), 1, SortOrder::first());
        $wings = savedCategory(12, 'Wings');

        expect(fn () => $wings->moveUnder($unsaved, hasSubCategories: false))
            ->toThrow(InvalidCategoryHierarchy::class, 'Save the parent');
    });

    it('stays top level when the move is refused because it has sub-categories', function (): void {
        $drinks = savedCategory(8, 'Drinks');

        expect(fn () => $drinks->moveUnder(savedCategory(3, 'Sides'), hasSubCategories: true))
            ->toThrow(InvalidCategoryHierarchy::class, 'has sub-categories');
        expect($drinks->isTopLevel())->toBeTrue();
    });

    it('lets a new, unsaved category move under a saved one', function (): void {
        $juice = Category::create('Juice', Slug::fromString('juice'), 7, SortOrder::first());

        $juice->moveUnder(savedCategory(8, 'Drinks'), hasSubCategories: false);

        expect($juice->parentId())->toBe(8);
    });

    it('turns its switches off and on again', function (): void {
        $category = savedCategory(1, 'Pizza');

        $category->deactivate();
        $category->feature();
        $category->hideFromMegaMenu();
        expect($category->isActive())->toBeFalse()
            ->and($category->isFeatured())->toBeTrue()
            ->and($category->isShownInMegaMenu())->toBeFalse();

        $category->activate();
        $category->unfeature();
        $category->showInMegaMenu();
        expect($category->isActive())->toBeTrue()
            ->and($category->isFeatured())->toBeFalse()
            ->and($category->isShownInMegaMenu())->toBeTrue();
    });

    it('changes its name, slug and position and removes its image', function (): void {
        $category = savedCategory(1, 'Pizza');
        $category->changeImage(ImagePath::of('categories/pizza.webp'));

        $category->rename('  Pizzas ');
        $category->changeSlug(Slug::fromString('pizzas'));
        $category->moveTo(SortOrder::fromInt(30));
        $category->changeImage(null);

        expect($category->name())->toBe('Pizzas')
            ->and($category->slug()->value())->toBe('pizzas')
            ->and($category->sort()->value())->toBe(30)
            ->and($category->image())->toBeNull();
    });
});
