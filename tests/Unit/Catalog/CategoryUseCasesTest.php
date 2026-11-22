<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateCategoryInput;
use App\Modules\Catalog\Application\DTOs\ImageUpload;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateCategoryInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Application\UseCases\Categories\CreateCategory;
use App\Modules\Catalog\Application\UseCases\Categories\DeleteCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ForceDeleteCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ReorderCategories;
use App\Modules\Catalog\Application\UseCases\Categories\RestoreCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ToggleCategoryActive;
use App\Modules\Catalog\Application\UseCases\Categories\ToggleCategoryFeatured;
use App\Modules\Catalog\Application\UseCases\Categories\UpdateCategory;
use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Catalog\Fakes\InMemoryCategoryRepository;
use Tests\Unit\Catalog\Fakes\InMemoryFileStorage;
use Tests\Unit\Catalog\Fakes\InMemoryKitchenStationRepository;
use Tests\Unit\Catalog\Fakes\RecordingImageProcessor;
use Tests\Unit\Identity\PassThroughTransactionManager;

/**
 * Input for CreateCategory with sensible defaults. Change only what a test is about.
 *
 * @param  array<string, mixed>  $changes  named constructor arguments
 */
function newCategoryInput(array $changes = []): CreateCategoryInput
{
    return new CreateCategoryInput(...[
        'name' => 'Drinks',
        'slug' => null,
        'parentId' => null,
        'kitchenStationId' => 1,
        'description' => null,
        'isActive' => true,
        'isFeatured' => false,
        'showInMegaMenu' => true,
        'image' => null,
        ...$changes,
    ]);
}

/**
 * Input for UpdateCategory: the given category, unchanged except for $changes.
 *
 * @param  array<string, mixed>  $changes  named constructor arguments
 */
function editCategoryInput(int $id, array $changes = []): UpdateCategoryInput
{
    return new UpdateCategoryInput(...[
        'id' => $id,
        'name' => 'Drinks',
        'slug' => null,
        'parentId' => null,
        'kitchenStationId' => 1,
        'description' => null,
        'isActive' => true,
        'isFeatured' => false,
        'showInMegaMenu' => true,
        'newImage' => null,
        'removeImage' => false,
        ...$changes,
    ]);
}

beforeEach(function (): void {
    $this->categories = new InMemoryCategoryRepository;
    $this->stations = new InMemoryKitchenStationRepository;
    $this->stations->save(KitchenStation::create('Drinks Bar', SortOrder::first())); // id 1
    $this->files = new InMemoryFileStorage;
    $this->images = new CatalogImages($this->files, new RecordingImageProcessor);
    $this->transactions = new PassThroughTransactionManager;

    $this->create = fn (array $changes = []): int => (new CreateCategory($this->categories, $this->stations, new SlugGenerator, $this->images))
        ->handle(newCategoryInput($changes));
    $this->update = fn (int $id, array $changes = []) => (new UpdateCategory($this->categories, $this->stations, $this->images))
        ->handle(editCategoryInput($id, $changes));
});

describe('creating', function (): void {
    it('creates a top-level category with a slug from the name, at the end of the top level', function (): void {
        ($this->create)(['name' => 'Pizza']);
        $id = ($this->create)(['name' => 'Soft Drinks']);

        $category = $this->categories->find($id);

        expect($category->slug()->value())->toBe('soft-drinks')
            ->and($category->isTopLevel())->toBeTrue()
            ->and($category->sort()->value())->toBe(20);
    });

    it('creates a sub-category at the end of its own group', function (): void {
        $drinks = ($this->create)(['name' => 'Drinks']);
        ($this->create)(['name' => 'Pizza']);

        $shakes = ($this->create)(['name' => 'Milkshakes', 'parentId' => $drinks]);

        expect($this->categories->find($shakes)->parentId())->toBe($drinks)
            ->and($this->categories->find($shakes)->sort()->value())->toBe(10);
    });

    it('applies the switches from the form', function (): void {
        $id = ($this->create)(['isActive' => false, 'isFeatured' => true, 'showInMegaMenu' => false]);

        $category = $this->categories->find($id);

        expect($category->isActive())->toBeFalse()
            ->and($category->isFeatured())->toBeTrue()
            ->and($category->isShownInMegaMenu())->toBeFalse();
    });

    it('refuses a sub-category as parent, under the parent field', function (): void {
        $drinks = ($this->create)(['name' => 'Drinks']);
        $shakes = ($this->create)(['name' => 'Milkshakes', 'parentId' => $drinks]);

        try {
            ($this->create)(['name' => 'Thick Shakes', 'parentId' => $shakes]);
            $this->fail('A sub-category was accepted as parent.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey('parent_id');
        }
    });

    it('refuses an unknown parent or kitchen station', function (string $field, array $changes): void {
        try {
            ($this->create)($changes);
            $this->fail('An unknown id was accepted.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey($field);
        }
    })->with([
        'parent' => ['parent_id', ['parentId' => 99]],
        'station' => ['kitchen_station_id', ['kitchenStationId' => 99]],
    ]);

    it('refuses a typed slug that is taken, also by a category in the trash', function (): void {
        $old = ($this->create)(['name' => 'Juice', 'slug' => 'juice']);
        $this->categories->delete($old);

        ($this->create)(['name' => 'Fresh Juice', 'slug' => 'juice']);
    })->throws(ValidationFailed::class);

    it('stores the image as a resized WebP original and a square WebP thumbnail', function (): void {
        $id = ($this->create)(['image' => new ImageUpload('photo')]);

        $image = $this->categories->find($id)->image();

        expect($image->original())->toBe('categories/file-1.webp')
            ->and($image->thumbnail())->toBe('categories/thumbs/file-2.webp')
            ->and($this->files->contents('categories/file-1.webp'))->toBe('webp(resize-1600x1600(photo))')
            ->and($this->files->contents('categories/thumbs/file-2.webp'))->toBe('webp(thumbnail-400(photo))');
    });

    it('deletes the new image files again when saving fails', function (): void {
        $this->categories->failNextSave();

        expect(fn () => ($this->create)(['image' => new ImageUpload('photo')]))->toThrow(RuntimeException::class);

        expect($this->files->paths())->toBe([]);
    });
});

describe('updating', function (): void {
    it('replaces the image and deletes the old files', function (): void {
        $id = ($this->create)(['image' => new ImageUpload('old')]);

        ($this->update)($id, ['newImage' => new ImageUpload('new')]);

        expect($this->categories->find($id)->image()->original())->toBe('categories/file-3.webp')
            ->and($this->files->paths())->toBe(['categories/file-3.webp', 'categories/thumbs/file-4.webp']);
    });

    it('removes the image and its files', function (): void {
        $id = ($this->create)(['image' => new ImageUpload('old')]);

        ($this->update)($id, ['removeImage' => true]);

        expect($this->categories->find($id)->image())->toBeNull()
            ->and($this->files->paths())->toBe([]);
    });

    it('keeps the old image when saving fails', function (): void {
        $id = ($this->create)(['image' => new ImageUpload('old')]);
        $this->categories->failNextSave();

        expect(fn () => ($this->update)($id, ['newImage' => new ImageUpload('new')]))->toThrow(RuntimeException::class);

        expect($this->categories->find($id)->image()->original())->toBe('categories/file-1.webp')
            ->and($this->files->paths())->toBe(['categories/file-1.webp', 'categories/thumbs/file-2.webp']);
    });

    it('moves a category under a parent, to the end of that group, and keeps its slug', function (): void {
        $drinks = ($this->create)(['name' => 'Drinks']);
        ($this->create)(['name' => 'Coffee', 'parentId' => $drinks]);
        $juice = ($this->create)(['name' => 'Juice']);

        ($this->update)($juice, ['name' => 'Fresh Juice', 'parentId' => $drinks]);

        $category = $this->categories->find($juice);

        expect($category->parentId())->toBe($drinks)
            ->and($category->sort()->value())->toBe(20)
            ->and($category->name())->toBe('Fresh Juice')
            ->and($category->slug()->value())->toBe('juice');
    });

    it('refuses to make a category with sub-categories a sub-category', function (): void {
        $drinks = ($this->create)(['name' => 'Drinks']);
        ($this->create)(['name' => 'Coffee', 'parentId' => $drinks]);
        $sides = ($this->create)(['name' => 'Sides']);

        try {
            ($this->update)($drinks, ['parentId' => $sides]);
            $this->fail('A category with sub-categories became a sub-category.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey('parent_id');
        }

        expect($this->categories->find($drinks)->isTopLevel())->toBeTrue();
    });

    it('reports an unknown category', function (): void {
        ($this->update)(99);
    })->throws(EntityNotFound::class);
});

describe('deleting', function (): void {
    it('refuses to delete a category with products or with sub-categories', function (): void {
        $delete = new DeleteCategory($this->categories, $this->transactions);
        $pizza = ($this->create)(['name' => 'Pizza']);
        $this->categories->giveProducts($pizza);
        $drinks = ($this->create)(['name' => 'Drinks']);
        ($this->create)(['name' => 'Coffee', 'parentId' => $drinks]);

        expect(fn () => $delete->handle(new CatalogItemInput($pizza)))->toThrow(CatalogItemInUse::class, 'still has products');
        expect(fn () => $delete->handle(new CatalogItemInput($drinks)))->toThrow(CatalogItemInUse::class, 'has sub-categories');
    });

    it('moves an unused category to the trash and restores it, image included', function (): void {
        $id = ($this->create)(['image' => new ImageUpload('photo')]);

        (new DeleteCategory($this->categories, $this->transactions))->handle(new CatalogItemInput($id));
        expect($this->categories->isDeleted($id))->toBeTrue()
            ->and($this->files->paths())->toHaveCount(2);

        (new RestoreCategory($this->categories))->handle(new CatalogItemInput($id));
        expect($this->categories->find($id)->image())->not->toBeNull();
    });

    it('deletes a category in the trash forever, with its image files', function (): void {
        $id = ($this->create)(['image' => new ImageUpload('photo')]);
        $this->categories->delete($id);

        (new ForceDeleteCategory($this->categories, $this->images))->handle(new CatalogItemInput($id));

        expect($this->categories->exists($id))->toBeFalse()
            ->and($this->files->paths())->toBe([]);
    });

    it('deletes forever only from the trash, and not while trashed products point to it', function (): void {
        $forceDelete = new ForceDeleteCategory($this->categories, $this->images);
        $live = ($this->create)(['name' => 'Pizza']);
        $trashed = ($this->create)(['name' => 'Soups']);
        $this->categories->giveTrashedProducts($trashed);
        $this->categories->delete($trashed);

        expect(fn () => $forceDelete->handle(new CatalogItemInput($live)))->toThrow(EntityNotFound::class);
        expect(fn () => $forceDelete->handle(new CatalogItemInput($trashed)))->toThrow(CatalogItemInUse::class, 'deleted forever');
        expect($this->categories->exists($trashed))->toBeTrue();
    });
});

describe('order and switches', function (): void {
    it('reorders the categories of one group', function (): void {
        $pizza = ($this->create)(['name' => 'Pizza']);
        $pasta = ($this->create)(['name' => 'Pasta']);

        (new ReorderCategories($this->categories, $this->transactions))->handle(new ReorderInput([$pasta, $pizza]));

        expect($this->categories->find($pasta)->sort()->value())->toBe(10)
            ->and($this->categories->find($pizza)->sort()->value())->toBe(20);
    });

    it('refuses to reorder categories of different parents together', function (): void {
        $drinks = ($this->create)(['name' => 'Drinks']);
        $coffee = ($this->create)(['name' => 'Coffee', 'parentId' => $drinks]);

        (new ReorderCategories($this->categories, $this->transactions))->handle(new ReorderInput([$coffee, $drinks]));
    })->throws(ValidationFailed::class);

    it('switches active and featured on and off', function (): void {
        $id = ($this->create)();

        expect((new ToggleCategoryActive($this->categories))->handle(new CatalogItemInput($id)))->toBeFalse()
            ->and((new ToggleCategoryFeatured($this->categories))->handle(new CatalogItemInput($id)))->toBeTrue()
            ->and($this->categories->find($id)->isActive())->toBeFalse()
            ->and($this->categories->find($id)->isFeatured())->toBeTrue();
    });
});
