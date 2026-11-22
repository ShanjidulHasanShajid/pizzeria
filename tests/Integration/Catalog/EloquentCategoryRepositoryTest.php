<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->repository = app(CategoryRepository::class);
    $this->stationId = KitchenStationModel::factory()->create()->id;
});

it('saves a new top-level category with all its fields and finds it again', function (): void {
    $id = $this->repository->save(Category::create(
        name: 'Drinks',
        slug: Slug::fromString('drinks'),
        kitchenStationId: $this->stationId,
        sort: SortOrder::fromInt(80),
        description: 'Cold and hot.',
        image: ImagePath::of('categories/drinks.webp', 'categories/thumbs/drinks.webp'),
    ));

    $drinks = $this->repository->find($id);

    expect($drinks->name())->toBe('Drinks')
        ->and($drinks->slug()->value())->toBe('drinks')
        ->and($drinks->description())->toBe('Cold and hot.')
        ->and($drinks->image()?->original())->toBe('categories/drinks.webp')
        ->and($drinks->image()?->thumbnail())->toBe('categories/thumbs/drinks.webp')
        ->and($drinks->kitchenStationId())->toBe($this->stationId)
        ->and($drinks->sort()->value())->toBe(80)
        ->and($drinks->isTopLevel())->toBeTrue()
        ->and($drinks->isActive())->toBeTrue()
        ->and($drinks->isShownInMegaMenu())->toBeTrue();
});

it('saves a sub-category with its parent and can move it to the top level', function (): void {
    $drinks = $this->repository->find(
        $this->repository->save(Category::create('Drinks', Slug::fromString('drinks'), $this->stationId, SortOrder::fromInt(80))),
    );
    $shakesId = $this->repository->save(
        Category::create('Milkshakes', Slug::fromString('milkshakes'), $this->stationId, SortOrder::fromInt(10), parent: $drinks),
    );

    $shakes = $this->repository->find($shakesId);
    expect($shakes->parentId())->toBe($drinks->id());

    $shakes->moveToTopLevel();
    $this->repository->save($shakes);

    expect($this->repository->find($shakesId)->isTopLevel())->toBeTrue();
});

it('updates a saved category instead of adding a second row', function (): void {
    $id = $this->repository->save(Category::create(
        name: 'Pizza',
        slug: Slug::fromString('pizza'),
        kitchenStationId: $this->stationId,
        sort: SortOrder::fromInt(10),
        image: ImagePath::of('categories/pizza.webp', 'categories/thumbs/pizza.webp'),
    ));
    $category = $this->repository->find($id);

    $category->rename('Pizzas');
    $category->feature();
    $category->hideFromMegaMenu();
    $category->deactivate();
    $category->changeImage(ImagePath::of('categories/pizzas.webp'));
    $this->repository->save($category);

    $pizzas = $this->repository->find($id);

    expect(CategoryModel::query()->count())->toBe(1)
        ->and($pizzas->name())->toBe('Pizzas')
        ->and($pizzas->isFeatured())->toBeTrue()
        ->and($pizzas->isShownInMegaMenu())->toBeFalse()
        ->and($pizzas->isActive())->toBeFalse()
        ->and($pizzas->image()?->original())->toBe('categories/pizzas.webp')
        ->and($pizzas->image()?->hasThumbnail())->toBeFalse();
});

it('moves a category to the Trash and back', function (): void {
    $id = CategoryModel::factory()->create()->id;

    $this->repository->delete($id);

    expect($this->repository->find($id))->toBeNull();
    $this->assertSoftDeleted('categories', ['id' => $id]);

    $this->repository->restore($id);

    expect($this->repository->find($id))->not->toBeNull();
});

it('finds a taken slug, also in the Trash, but not the slug of the category being edited', function (): void {
    $id = $this->repository->save(Category::create('Pizza', Slug::fromString('pizza'), $this->stationId, SortOrder::fromInt(10)));

    expect($this->repository->slugExists(Slug::fromString('pizza')))->toBeTrue()
        ->and($this->repository->slugExists(Slug::fromString('pasta')))->toBeFalse()
        ->and($this->repository->slugExists(Slug::fromString('pizza'), ignoreId: $id))->toBeFalse();

    $this->repository->delete($id);

    expect($this->repository->slugExists(Slug::fromString('pizza')))->toBeTrue();
});

it('knows when a product that is not deleted belongs to a category', function (): void {
    $pizza = CategoryModel::factory()->create();
    $pasta = CategoryModel::factory()->create();
    $margherita = ProductModel::factory()->for($pizza)->create();

    expect($this->repository->hasProducts($pizza->id))->toBeTrue()
        ->and($this->repository->hasProducts($pasta->id))->toBeFalse();

    $margherita->delete();

    expect($this->repository->hasProducts($pizza->id))->toBeFalse();
});

it('knows when a sub-category that is not deleted belongs to a category', function (): void {
    $drinks = CategoryModel::factory()->create();
    $shakes = CategoryModel::factory()->childOf($drinks)->create();

    expect($this->repository->hasChildren($drinks->id))->toBeTrue()
        ->and($this->repository->hasChildren($shakes->id))->toBeFalse();

    $shakes->delete();

    expect($this->repository->hasChildren($drinks->id))->toBeFalse();
});

it('gives the position after the last category with the same parent', function (): void {
    expect($this->repository->nextSortOrder(null)->value())->toBe(10);

    $drinks = CategoryModel::factory()->create(['sort' => 20]);
    CategoryModel::factory()->create(['sort' => 90])->delete();
    CategoryModel::factory()->childOf($drinks)->create(['sort' => 40]);

    expect($this->repository->nextSortOrder(null)->value())->toBe(30)
        ->and($this->repository->nextSortOrder($drinks->id)->value())->toBe(50)
        ->and($this->repository->nextSortOrder(424242)->value())->toBe(10);
});

it('finds a category in the trash and deletes it forever', function (): void {
    $id = CategoryModel::factory()->create(['name' => 'Soups'])->id;

    expect($this->repository->findTrashed($id))->toBeNull();

    $this->repository->delete($id);

    expect($this->repository->findTrashed($id)?->name())->toBe('Soups');

    $this->repository->forceDelete($id);

    $this->assertDatabaseMissing('categories', ['id' => $id]);
});

it('knows when products or sub-categories still point to a category, also from the trash', function (): void {
    $soups = CategoryModel::factory()->create();
    $drinks = CategoryModel::factory()->create();
    $empty = CategoryModel::factory()->create();
    ProductModel::factory()->for($soups)->create()->delete();
    CategoryModel::factory()->childOf($drinks)->create()->delete();

    expect($this->repository->isUsedIncludingTrash($soups->id))->toBeTrue()
        ->and($this->repository->isUsedIncludingTrash($drinks->id))->toBeTrue()
        ->and($this->repository->isUsedIncludingTrash($empty->id))->toBeFalse();
});
