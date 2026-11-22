<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\QueryException;

/**
 * A new, unsaved Margherita: Personal (default), Medium and Large, linked to three size tiers.
 * (The name is unique across all test files: Pest loads them into one PHP process.)
 *
 * @param  list<int>  $sizeTierIds  three ids: Personal, Medium, Large
 */
function unsavedPizza(int $categoryId, array $sizeTierIds, string $slug = 'margherita'): Product
{
    $pizza = Product::create(
        type: ProductType::Standard,
        categoryId: $categoryId,
        name: 'Margherita',
        slug: Slug::fromString($slug),
        leadTime: LeadTime::madeToOrder(20),
        firstVariant: Variant::create('Personal', Money::fromTaka('450'), SortOrder::fromInt(10), sizeTierId: $sizeTierIds[0]),
        sort: SortOrder::fromInt(10),
        shortDescription: 'Tomato, mozzarella, basil.',
    );
    $pizza->addVariant(Variant::create('Medium', Money::fromTaka('850'), SortOrder::fromInt(20), sizeTierId: $sizeTierIds[1]));
    $pizza->addVariant(Variant::create('Large', Money::fromTaka('1250'), SortOrder::fromInt(30), sizeTierId: $sizeTierIds[2]));

    return $pizza;
}

beforeEach(function (): void {
    $this->repository = app(ProductRepository::class);
    $this->categoryId = CategoryModel::factory()->create()->id;
    $this->sizeTierIds = SizeTierModel::factory()->count(3)->create()->modelKeys();
});

it('saves a new product with variants, image, badges and tags and loads it back', function (): void {
    $tagIds = TagModel::factory()->count(2)->create()->modelKeys();
    $pizza = unsavedPizza($this->categoryId, $this->sizeTierIds);
    $pizza->addImage(ImagePath::of('products/margherita.webp', 'products/thumbs/margherita.webp'));
    $pizza->assignTags($tagIds);
    $pizza->changeBadges(ProductBadges::of(isNew: true, isHot: true));
    $pizza->publish();

    $loaded = $this->repository->find($this->repository->save($pizza));

    expect($loaded->name())->toBe('Margherita')
        ->and($loaded->slug()->value())->toBe('margherita')
        ->and($loaded->categoryId())->toBe($this->categoryId)
        ->and($loaded->shortDescription())->toBe('Tomato, mozzarella, basil.')
        ->and($loaded->leadTime()->minutes())->toBe(20)
        ->and($loaded->badges()->isHot())->toBeTrue()
        ->and($loaded->isPublished())->toBeTrue()
        ->and(array_map(fn (Variant $variant): string => $variant->label(), $loaded->variants()))->toBe(['Personal', 'Medium', 'Large'])
        ->and($loaded->defaultVariant()->label())->toBe('Personal')
        ->and($loaded->variant('Medium')->price()->poisha())->toBe(85_000)
        ->and($loaded->variant('Large')->sizeTierId())->toBe($this->sizeTierIds[2])
        ->and($loaded->mainImage()?->path()->thumbnail())->toBe('products/thumbs/margherita.webp')
        ->and($loaded->tagIds())->toBe($tagIds);
});

it('keeps a gift box a gift box', function (): void {
    $box = Product::create(
        type: ProductType::Combo,
        categoryId: $this->categoryId,
        name: 'Birthday Box',
        slug: Slug::fromString('birthday-box'),
        leadTime: LeadTime::nextDay(1440),
        firstVariant: Variant::create('Box', Money::fromTaka('2500'), SortOrder::first()),
        sort: SortOrder::first(),
    );
    $box->markGiftable();

    $loaded = $this->repository->find($this->repository->save($box));

    expect($loaded->type())->toBe(ProductType::Combo)
        ->and($loaded->isGiftable())->toBeTrue()
        ->and($loaded->leadTime()->leadTimeClass())->toBe(LeadTimeClass::NextDay);
});

it('updates the same rows when a saved product changes', function (): void {
    $id = $this->repository->save(unsavedPizza($this->categoryId, $this->sizeTierIds));
    $mediumRowId = ProductVariantModel::query()->where('label', 'Medium')->firstOrFail()->id;

    $pizza = $this->repository->find($id);
    $pizza->rename('Margherita Classic');
    $pizza->updateVariant('Medium', 'Medium', Money::fromTaka('899'), $this->sizeTierIds[1]);
    $this->repository->save($pizza);

    $loaded = $this->repository->find($id);

    expect(ProductModel::query()->count())->toBe(1)
        ->and(ProductVariantModel::query()->count())->toBe(3)
        ->and($loaded->name())->toBe('Margherita Classic')
        ->and($loaded->variant('Medium')->id())->toBe($mediumRowId)
        ->and($loaded->variant('Medium')->price()->poisha())->toBe(89_900);
});

it('soft-deletes a removed variant and lets the same label come back later', function (): void {
    $id = $this->repository->save(unsavedPizza($this->categoryId, $this->sizeTierIds));

    $pizza = $this->repository->find($id);
    $pizza->removeVariant('Large');
    $this->repository->save($pizza);

    expect($this->repository->find($id)->variants())->toHaveCount(2);
    $this->assertSoftDeleted('product_variants', ['product_id' => $id, 'label' => 'Large']);

    $pizza = $this->repository->find($id);
    $pizza->addVariant(Variant::create('Large', Money::fromTaka('1300'), SortOrder::fromInt(30), sizeTierId: $this->sizeTierIds[2]));
    $this->repository->save($pizza);

    expect($this->repository->find($id)->variant('Large')->price()->poisha())->toBe(130_000)
        ->and(ProductVariantModel::withTrashed()->where('product_id', $id)->count())->toBe(4);
});

it('really deletes a removed image and saves the new main image', function (): void {
    $pizza = unsavedPizza($this->categoryId, $this->sizeTierIds);
    $pizza->addImage(ImagePath::of('products/a.webp'));
    $pizza->addImage(ImagePath::of('products/b.webp'));
    $id = $this->repository->save($pizza);

    $pizza = $this->repository->find($id);
    $pizza->removeImage('products/a.webp');
    $this->repository->save($pizza);

    expect(ProductImageModel::query()->count())->toBe(1)
        ->and($this->repository->find($id)->mainImage()?->path()->original())->toBe('products/b.webp');
    $this->assertDatabaseMissing('product_images', ['path' => 'products/a.webp']);
});

it('replaces the tag links on save', function (): void {
    [$spicy, $veg, $halal] = TagModel::factory()->count(3)->create()->modelKeys();
    $pizza = unsavedPizza($this->categoryId, $this->sizeTierIds);
    $pizza->assignTags([$spicy, $veg]);
    $id = $this->repository->save($pizza);

    $pizza = $this->repository->find($id);
    $pizza->assignTags([$halal]);
    $this->repository->save($pizza);

    expect($this->repository->find($id)->tagIds())->toBe([$halal]);
});

it('keeps the link to a tag that is in the Trash', function (): void {
    [$spicy, $veg] = TagModel::factory()->count(2)->create()->all();
    $pizza = unsavedPizza($this->categoryId, $this->sizeTierIds);
    $pizza->assignTags([$spicy->id, $veg->id]);
    $id = $this->repository->save($pizza);

    $veg->delete();
    $this->repository->save($this->repository->find($id));

    expect($this->repository->find($id)->tagIds())->toBe([$spicy->id, $veg->id]);
    $this->assertDatabaseHas('product_tag', ['product_id' => $id, 'tag_id' => $veg->id]);
});

it('saves nothing when one part of the product cannot be saved', function (): void {
    $broken = unsavedPizza($this->categoryId, [$this->sizeTierIds[0], $this->sizeTierIds[1], 999_999]);

    expect(fn () => $this->repository->save($broken))->toThrow(QueryException::class);

    expect(ProductModel::withTrashed()->count())->toBe(0)
        ->and(ProductVariantModel::withTrashed()->count())->toBe(0);
});

it('moves a product to the Trash and back', function (): void {
    $id = $this->repository->save(unsavedPizza($this->categoryId, $this->sizeTierIds));

    $this->repository->delete($id);

    expect($this->repository->find($id))->toBeNull();
    $this->assertSoftDeleted('products', ['id' => $id]);

    $this->repository->restore($id);

    expect($this->repository->find($id)?->variants())->toHaveCount(3);
});

it('finds several products by id and skips unknown and deleted ones', function (): void {
    $margherita = $this->repository->save(unsavedPizza($this->categoryId, $this->sizeTierIds, 'margherita'));
    $pepperoni = $this->repository->save(unsavedPizza($this->categoryId, $this->sizeTierIds, 'pepperoni'));
    $hawaiian = $this->repository->save(unsavedPizza($this->categoryId, $this->sizeTierIds, 'hawaiian'));
    $this->repository->delete($hawaiian);

    $found = $this->repository->findByIds([$pepperoni, 424242, $hawaiian, $margherita]);

    expect(array_map(fn (Product $product): ?int => $product->id(), $found))->toEqualCanonicalizing([$margherita, $pepperoni])
        ->and($this->repository->findByIds([]))->toBe([]);
});

it('finds a taken slug, also in the Trash, but not the slug of the product being edited', function (): void {
    $id = $this->repository->save(unsavedPizza($this->categoryId, $this->sizeTierIds));

    expect($this->repository->slugExists(Slug::fromString('margherita')))->toBeTrue()
        ->and($this->repository->slugExists(Slug::fromString('pepperoni')))->toBeFalse()
        ->and($this->repository->slugExists(Slug::fromString('margherita'), ignoreId: $id))->toBeFalse();

    $this->repository->delete($id);

    expect($this->repository->slugExists(Slug::fromString('margherita')))->toBeTrue();
});
