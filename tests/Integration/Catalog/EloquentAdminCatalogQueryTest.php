<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\AdminCategoryFilter;
use App\Modules\Catalog\Application\DTOs\AdminCategoryRow;
use App\Modules\Catalog\Application\DTOs\AdminProductFilter;
use App\Modules\Catalog\Application\DTOs\AdminProductRow;
use App\Modules\Catalog\Application\DTOs\AdminVariantRow;
use App\Modules\Catalog\Application\DTOs\KitchenStationRow;
use App\Modules\Catalog\Application\DTOs\SizeTierRow;
use App\Modules\Catalog\Application\DTOs\TagRow;
use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;

beforeEach(function (): void {
    $this->query = app(AdminCatalogQuery::class);
});

describe('the category table', function (): void {
    it('lists each top-level category followed by its sub-categories', function (): void {
        $oven = KitchenStationModel::factory()->create(['name' => 'Oven']);
        $bar = KitchenStationModel::factory()->create(['name' => 'Drinks Bar']);
        $drinks = CategoryModel::factory()->for($bar)->create(['name' => 'Drinks', 'sort' => 20]);
        $pizza = CategoryModel::factory()->for($oven)->create(['name' => 'Pizza', 'sort' => 10]);
        CategoryModel::factory()->childOf($drinks)->create(['name' => 'Milkshakes', 'sort' => 10]);
        ProductModel::factory()->count(2)->for($pizza)->create();
        ProductModel::factory()->for($pizza)->create()->delete();

        $rows = $this->query->categoryTable();

        expect(array_map(fn (AdminCategoryRow $row): string => $row->name, $rows))->toBe(['Pizza', 'Drinks', 'Milkshakes'])
            ->and($rows[0]->productCount)->toBe(2)
            ->and($rows[0]->kitchenStationName)->toBe('Oven')
            ->and($rows[2]->parentName)->toBe('Drinks')
            ->and($rows[2]->kitchenStationName)->toBe('Drinks Bar');
    });

    it('still lists a sub-category whose parent is in the Trash', function (): void {
        $drinks = CategoryModel::factory()->create(['name' => 'Drinks']);
        CategoryModel::factory()->childOf($drinks)->create(['name' => 'Milkshakes']);
        $drinks->delete();

        $rows = $this->query->categoryTable();

        expect(array_map(fn (AdminCategoryRow $row): string => $row->name, $rows))->toBe(['Milkshakes'])
            ->and($rows[0]->parentName)->toBeNull();
    });
});

describe('the product table', function (): void {
    it('shows the lowest and highest price and the number of variants, without deleted variants', function (): void {
        $pizza = ProductModel::factory()
            ->has(ProductVariantModel::factory()->asDefault()->state(['price' => 45_000]), 'variants')
            ->has(ProductVariantModel::factory()->state(['price' => 125_000]), 'variants')
            ->has(ProductVariantModel::factory()->state(['price' => 10_000]), 'variants')
            ->create();
        $pizza->variants()->where('price', 10_000)->firstOrFail()->delete();

        $row = $this->query->productTable(new AdminProductFilter)->items[0];

        expect($row->lowestPrice->poisha())->toBe(45_000)
            ->and($row->highestPrice->poisha())->toBe(125_000)
            ->and($row->variantCount)->toBe(2)
            ->and($row->categoryName)->toBe($pizza->category->name);
    });

    it('filters by search, category, type, tag and status', function (): void {
        $pizza = CategoryModel::factory()->create();
        $spicy = TagModel::factory()->create();
        $margherita = ProductModel::factory()->for($pizza)->create(['name' => 'Margherita', 'slug' => 'margherita']);
        $pepperoni = ProductModel::factory()->for($pizza)->unpublished()->create(['name' => 'Pepperoni', 'slug' => 'pepperoni']);
        $pepperoni->tags()->attach($spicy);
        $deal = ProductModel::factory()->combo()->soldOut()->create(['name' => 'Family Deal', 'slug' => 'family-deal']);
        $old = ProductModel::factory()->for($pizza)->create(['name' => 'Old Pizza', 'slug' => 'old-pizza']);
        $old->delete();

        $ids = fn (AdminProductFilter $filter): array => array_map(
            fn (AdminProductRow $row): int => $row->id,
            $this->query->productTable($filter)->items,
        );

        expect($ids(new AdminProductFilter))->toEqualCanonicalizing([$margherita->id, $pepperoni->id, $deal->id])
            ->and($ids(new AdminProductFilter(search: 'PEPPER')))->toBe([$pepperoni->id])
            ->and($ids(new AdminProductFilter(search: 'family-')))->toBe([$deal->id])
            ->and($ids(new AdminProductFilter(categoryId: $pizza->id)))->toEqualCanonicalizing([$margherita->id, $pepperoni->id])
            ->and($ids(new AdminProductFilter(type: ProductType::Combo)))->toBe([$deal->id])
            ->and($ids(new AdminProductFilter(tagId: $spicy->id)))->toBe([$pepperoni->id])
            ->and($ids(new AdminProductFilter(status: 'published')))->toEqualCanonicalizing([$margherita->id, $deal->id])
            ->and($ids(new AdminProductFilter(status: 'unpublished')))->toBe([$pepperoni->id])
            ->and($ids(new AdminProductFilter(status: 'sold_out')))->toBe([$deal->id])
            ->and($ids(new AdminProductFilter(status: 'trashed')))->toBe([$old->id]);
    });

    it('splits the products into pages', function (): void {
        ProductModel::factory()->count(5)->create();

        $page = $this->query->productTable(new AdminProductFilter(page: 2, perPage: 2));

        expect($page->total)->toBe(5)
            ->and($page->page)->toBe(2)
            ->and($page->perPage)->toBe(2)
            ->and($page->items)->toHaveCount(2);
    });

    it('names the category of a product even when the category is in the Trash', function (): void {
        $soups = CategoryModel::factory()->create(['name' => 'Soups']);
        ProductModel::factory()->for($soups)->create();
        $soups->delete();

        expect($this->query->productTable(new AdminProductFilter)->items[0]->categoryName)->toBe('Soups');
    });
});

it('lists everything in the Trash, newest deletion first', function (): void {
    KitchenStationModel::factory()->create(['name' => 'Old Grill', 'deleted_at' => '2026-11-01 10:00:00']);
    TagModel::factory()->create(['name' => 'Mild', 'deleted_at' => '2026-11-02 10:00:00']);
    SizeTierModel::factory()->create(['name' => 'Mini', 'deleted_at' => '2026-11-03 10:00:00']);
    ProductModel::factory()->create(['name' => 'Tomato Soup', 'deleted_at' => '2026-11-04 10:00:00']);
    CategoryModel::factory()->create(['name' => 'Soups', 'deleted_at' => '2026-11-05 10:00:00']);
    CategoryModel::factory()->create(['name' => 'Pasta']);

    $items = $this->query->trashedItems();

    expect(array_map(fn (TrashedCatalogItem $item): string => "{$item->kind}: {$item->name}", $items))->toBe([
        'category: Soups',
        'product: Tomato Soup',
        'size_tier: Mini',
        'tag: Mild',
        'kitchen_station: Old Grill',
    ]);
});

it('lists kitchen stations in sort order with the number of categories that use them', function (): void {
    $wok = KitchenStationModel::factory()->create(['name' => 'Wok', 'sort' => 20]);
    $tandoor = KitchenStationModel::factory()->create(['name' => 'Tandoor', 'sort' => 10]);
    CategoryModel::factory()->count(2)->for($wok)->create();
    CategoryModel::factory()->for($wok)->create()->delete();
    KitchenStationModel::factory()->create(['name' => 'Old Grill'])->delete();

    $rows = $this->query->kitchenStationTable();

    expect(array_map(fn (KitchenStationRow $row): string => $row->name, $rows))->toBe(['Tandoor', 'Wok'])
        ->and($rows[1]->categoryCount)->toBe(2)
        ->and($this->query->kitchenStation($tandoor->id)?->categoryCount)->toBe(0)
        ->and($this->query->kitchenStation(424242))->toBeNull();
});

it('lists size tiers in sort order with the number of variants that use them', function (): void {
    $medium = SizeTierModel::factory()->create(['name' => 'Medium', 'sort' => 20]);
    SizeTierModel::factory()->create(['name' => 'Personal', 'sort' => 10]);
    ProductVariantModel::factory()->forSizeTier($medium)->create();
    ProductVariantModel::factory()->forSizeTier($medium)->create(['label' => 'Medium Thin'])->delete();

    $rows = $this->query->sizeTierTable();

    expect(array_map(fn (SizeTierRow $row): string => $row->name, $rows))->toBe(['Personal', 'Medium'])
        ->and($rows[1]->variantCount)->toBe(1)
        ->and($this->query->sizeTier($medium->id)?->slug)->toBe($medium->slug)
        ->and($this->query->sizeTier(424242))->toBeNull();
});

it('lists tags in sort order with the number of products that have them', function (): void {
    $spicy = TagModel::factory()->create(['name' => 'Spicy', 'type' => TagType::Flavour, 'icon' => 'fire', 'sort' => 20]);
    TagModel::factory()->create(['name' => 'Veg', 'sort' => 10]);
    ProductModel::factory()->create()->tags()->attach($spicy);
    $deleted = ProductModel::factory()->create();
    $deleted->tags()->attach($spicy);
    $deleted->delete();

    $rows = $this->query->tagTable();

    expect(array_map(fn (TagRow $row): string => $row->name, $rows))->toBe(['Veg', 'Spicy'])
        ->and($rows[1]->productCount)->toBe(1)
        ->and($rows[1]->type)->toBe(TagType::Flavour)
        ->and($rows[1]->icon)->toBe('fire')
        ->and($this->query->tag(424242))->toBeNull();
});

it('filters the category list by search and status, and lists the trash', function (): void {
    $drinks = CategoryModel::factory()->create(['name' => 'Drinks', 'slug' => 'drinks']);
    CategoryModel::factory()->childOf($drinks)->create(['name' => 'Milkshakes', 'slug' => 'milkshakes']);
    CategoryModel::factory()->inactive()->create(['name' => 'Soups', 'slug' => 'soups']);
    CategoryModel::factory()->create(['name' => 'Old Pies', 'slug' => 'old-pies'])->delete();

    $names = fn (AdminCategoryFilter $filter): array => array_map(
        fn (AdminCategoryRow $row): string => $row->name,
        $this->query->categoryTable($filter),
    );

    expect($names(new AdminCategoryFilter(search: 'shake')))->toBe(['Milkshakes'])
        ->and($this->query->categoryTable(new AdminCategoryFilter(search: 'shake'))[0]->parentName)->toBe('Drinks')
        ->and($names(new AdminCategoryFilter(status: 'active')))->toEqualCanonicalizing(['Drinks', 'Milkshakes'])
        ->and($names(new AdminCategoryFilter(status: 'inactive')))->toBe(['Soups'])
        ->and($names(new AdminCategoryFilter(status: 'trashed')))->toBe(['Old Pies'])
        ->and($this->query->categoryTable(new AdminCategoryFilter(status: 'trashed'))[0]->isDeleted)->toBeTrue();
});

it('gives the details for the category form and the parent choices', function (): void {
    $drinks = CategoryModel::factory()->create([
        'name' => 'Drinks', 'sort' => 20, 'image_path' => 'categories/a.webp', 'image_thumbnail_path' => 'categories/thumbs/a.webp',
    ]);
    $pizza = CategoryModel::factory()->create(['name' => 'Pizza', 'sort' => 10]);
    $shakes = CategoryModel::factory()->childOf($drinks)->create(['name' => 'Milkshakes']);
    CategoryModel::factory()->create(['name' => 'Gone'])->delete();

    $detail = $this->query->category($drinks->id);

    expect($detail?->childCount)->toBe(1)
        ->and($detail?->imagePath)->toBe('categories/thumbs/a.webp')
        ->and($detail?->kitchenStationId)->toBe($drinks->kitchen_station_id)
        ->and($this->query->category($shakes->id)?->parentId)->toBe($drinks->id)
        ->and($this->query->category(424242))->toBeNull()
        ->and($this->query->topLevelCategoryOptions())->toBe([$pizza->id => 'Pizza', $drinks->id => 'Drinks']);
});

it('gives a product for the edit form with variants, images and tags in order', function (): void {
    $product = ProductModel::factory()
        ->has(ProductVariantModel::factory()->state(['label' => 'Large', 'price' => 125_050, 'sort' => 20]), 'variants')
        ->has(ProductVariantModel::factory()->asDefault()->state(['label' => 'Personal', 'price' => 45_000, 'sort' => 10]), 'variants')
        ->create();
    ProductImageModel::factory()->for($product)->create(['path' => 'products/b.webp', 'sort' => 20]);
    ProductImageModel::factory()->for($product)->main()->create(['path' => 'products/a.webp', 'thumbnail_path' => 'products/thumbs/a.webp', 'sort' => 10]);
    $veg = TagModel::factory()->create();
    $gone = TagModel::factory()->create();
    $product->tags()->attach([$veg->id, $gone->id]);
    $gone->delete();

    $detail = $this->query->product($product->id);

    expect(array_map(fn (AdminVariantRow $variant): string => $variant->label, $detail->variants))->toBe(['Personal', 'Large'])
        ->and($detail->variants[1]->priceTaka)->toBe('1250.50')
        ->and($detail->variants[0]->isDefault)->toBeTrue()
        ->and($detail->images[0]->originalPath)->toBe('products/a.webp')
        ->and($detail->images[0]->thumbnailPath)->toBe('products/thumbs/a.webp')
        ->and($detail->images[1]->thumbnailPath)->toBe('products/b.webp')
        ->and($detail->tagIds)->toBe([$veg->id, $gone->id])
        ->and($this->query->product(424242))->toBeNull();
});

it('lists the products of a category and its sub-categories that may have options', function (): void {
    $drinks = CategoryModel::factory()->create();
    $coffee = CategoryModel::factory()->childOf($drinks)->create();
    $water = ProductModel::factory()->for($drinks)->create(['sort' => 20]);
    $latte = ProductModel::factory()->for($coffee)->unpublished()->create(['sort' => 10]);
    ProductModel::factory()->for($drinks)->combo()->create();
    ProductModel::factory()->for($drinks)->create()->delete();
    ProductModel::factory()->create();

    expect($this->query->customizableProductIds($drinks->id))->toBe([$latte->id, $water->id])
        ->and($this->query->customizableProductIds($coffee->id))->toBe([$latte->id])
        ->and($this->query->customizableProductIds(424242))->toBeNull();
});

it('knows which products may have options', function (): void {
    $product = ProductModel::factory()->create();
    $combo = ProductModel::factory()->combo()->create();
    $deleted = ProductModel::factory()->create();
    $deleted->delete();

    expect($this->query->isCustomizableProduct($product->id))->toBeTrue()
        ->and($this->query->isCustomizableProduct($combo->id))->toBeFalse()
        ->and($this->query->isCustomizableProduct($deleted->id))->toBeFalse()
        ->and($this->query->isCustomizableProduct(424242))->toBeFalse();
});
