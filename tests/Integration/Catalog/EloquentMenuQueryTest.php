<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Catalog\Application\DTOs\MenuFilter;
use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\TagBadge;
use App\Modules\Catalog\Application\Queries\MenuQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Domain\Enums\ProductSort;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->menu = app(MenuQuery::class);
    $this->pizza = CategoryModel::factory()->create(['name' => 'Pizza', 'slug' => 'pizza', 'sort' => 10]);

    // A product with variants in taka: [price, available] each.
    $this->product = function (string $name, array $variants = [[300, true]], array $attributes = [], ?CategoryModel $category = null): ProductModel {
        $factory = ProductModel::factory()->for($category ?? $this->pizza)->state(['name' => $name, 'sort' => 10, ...$attributes]);

        foreach ($variants as $position => [$price, $isAvailable]) {
            $factory = $factory->has(ProductVariantModel::factory()->state([
                'label' => "Size {$position}", 'price' => $price * 100, 'is_available' => $isAvailable, 'is_default' => $position === 0, 'sort' => ($position + 1) * 10,
            ]), 'variants');
        }

        return $factory->create();
    };
    $this->names = fn (MenuFilter $filter): array => array_map(fn (ProductCard $card): string => $card->name, $this->menu->listing($filter)->items);
});

it('lists every active category with its active sub-categories', function (): void {
    $drinks = CategoryModel::factory()->hiddenFromMegaMenu()->create(['name' => 'Drinks', 'sort' => 20]);
    CategoryModel::factory()->childOf($drinks)->create(['name' => 'Coffee']);
    CategoryModel::factory()->childOf($drinks)->inactive()->create(['name' => 'Beer']);
    CategoryModel::factory()->inactive()->create(['name' => 'Closed', 'sort' => 5]);

    $categories = $this->menu->categories();

    expect(array_map(fn (CategoryNode $node): string => $node->name, $categories))->toBe(['Pizza', 'Drinks'])
        ->and(array_map(fn (CategoryNode $node): string => $node->name, $categories[1]->children))->toBe(['Coffee']);
});

it('groups the whole menu and leaves empty categories out', function (): void {
    $drinks = CategoryModel::factory()->create(['name' => 'Drinks', 'sort' => 20]);
    $coffee = CategoryModel::factory()->childOf($drinks)->create();
    CategoryModel::factory()->create(['name' => 'Empty', 'sort' => 30]);
    ($this->product)('Margherita');
    ($this->product)('Hidden', attributes: ['is_published' => false]);
    ($this->product)('Latte', category: $coffee);

    $sections = array_map(
        fn (CategorySection $section): array => [$section->name, array_map(fn (ProductCard $card): string => $card->name, $section->products)],
        $this->menu->sections(),
    );

    expect($sections)->toBe([['Pizza', ['Margherita']], ['Drinks', ['Latte']]]);
});

it('finds visible categories and sub-categories', function (): void {
    $drinks = CategoryModel::factory()->create(['name' => 'Drinks', 'slug' => 'drinks']);
    CategoryModel::factory()->childOf($drinks)->create(['name' => 'Coffee', 'slug' => 'coffee']);
    $closed = CategoryModel::factory()->inactive()->create(['slug' => 'closed']);
    CategoryModel::factory()->childOf($closed)->create(['slug' => 'child-of-closed']);
    CategoryModel::factory()->create(['slug' => 'deleted'])->delete();

    expect($this->menu->category('drinks')->parentSlug)->toBeNull()
        ->and($this->menu->category('drinks')->children[0]->name)->toBe('Coffee')
        ->and($this->menu->category('coffee')->parentName)->toBe('Drinks')
        ->and($this->menu->category('coffee')->children)->toBe([])
        ->and($this->menu->category('closed'))->toBeNull()
        ->and($this->menu->category('child-of-closed'))->toBeNull()
        ->and($this->menu->category('deleted'))->toBeNull()
        ->and($this->menu->category('unknown'))->toBeNull();
});

it('finds active tags and offers no allergens as a filter', function (): void {
    TagModel::factory()->create(['name' => 'Spicy', 'slug' => 'spicy', 'type' => TagType::Flavour, 'sort' => 20]);
    TagModel::factory()->create(['name' => 'Veg', 'slug' => 'veg', 'type' => TagType::Dietary, 'sort' => 10]);
    TagModel::factory()->create(['name' => 'Nuts', 'slug' => 'nuts', 'type' => TagType::Allergen]);
    TagModel::factory()->create(['slug' => 'old', 'type' => TagType::Dietary, 'is_active' => false]);

    expect(array_map(fn (TagBadge $tag): string => $tag->name, $this->menu->filterTags()))->toBe(['Veg', 'Spicy'])
        ->and($this->menu->tag('nuts')->name)->toBe('Nuts')
        ->and($this->menu->tag('old'))->toBeNull()
        ->and($this->menu->tag('unknown'))->toBeNull();
});

it('lists a category with its sub-categories, without hidden products', function (): void {
    $drinks = CategoryModel::factory()->create();
    $coffee = CategoryModel::factory()->childOf($drinks)->create();
    $beer = CategoryModel::factory()->childOf($drinks)->inactive()->create();
    ($this->product)('Margherita');
    ($this->product)('Cola', category: $drinks);
    ($this->product)('Latte', category: $coffee);
    ($this->product)('Lager', category: $beer);
    ($this->product)('Secret', attributes: ['is_published' => false], category: $drinks);

    expect(($this->names)(new MenuFilter(categoryId: $drinks->id)))->toBe(['Cola', 'Latte'])
        ->and(($this->names)(new MenuFilter(categoryId: $coffee->id)))->toBe(['Latte'])
        ->and(($this->names)(new MenuFilter))->toBe(['Margherita', 'Cola', 'Latte']);
});

it('wants all chosen tags, and counts active tags only', function (): void {
    $veg = TagModel::factory()->create(['slug' => 'veg']);
    $spicy = TagModel::factory()->create(['slug' => 'spicy']);
    $old = TagModel::factory()->create(['slug' => 'old', 'is_active' => false]);
    ($this->product)('Only veg')->tags()->attach($veg);
    ($this->product)('Both')->tags()->attach([$veg->id, $spicy->id, $old->id]);
    ($this->product)('None');

    expect(($this->names)(new MenuFilter(tagSlugs: ['veg'])))->toBe(['Only veg', 'Both'])
        ->and(($this->names)(new MenuFilter(tagSlugs: ['veg', 'spicy'])))->toBe(['Both'])
        ->and(($this->names)(new MenuFilter(pageTag: 'spicy', tagSlugs: ['veg'])))->toBe(['Both'])
        ->and(($this->names)(new MenuFilter(tagSlugs: ['old'])))->toBe([])
        ->and(($this->names)(new MenuFilter(tagSlugs: ['unknown'])))->toBe([]);
});

it('filters by the price on the card', function (int $min, int $max, array $names): void {
    ($this->product)('Small', [[200, true]]);
    ($this->product)('From 400', [[300, false], [400, true], [900, true]]); // the 300 size is sold out
    ($this->product)('Sold out', [[500, false], [700, false]]);

    expect(($this->names)(new MenuFilter(minPrice: Money::fromTaka($min), maxPrice: Money::fromTaka($max))))->toBe($names);
})->with([
    'everything' => [0, 1000, ['Small', 'From 400', 'Sold out']],
    'the sold out size does not count' => [300, 350, []],
    'the limits are inside' => [200, 400, ['Small', 'From 400']],
    'all sizes sold out: the cheapest counts' => [500, 500, ['Sold out']],
    'the wrong way round' => [500, 200, []],
]);

it('ignores a deleted size when it compares prices', function (): void {
    $product = ($this->product)('Pizza', [[200, true], [600, true]]);
    $product->variants()->where('price', 20_000)->firstOrFail()->delete();

    expect(($this->names)(new MenuFilter(maxPrice: Money::fromTaka(300))))->toBe([]);
});

it('shows only what can be ordered now', function (): void {
    ($this->product)('Available', [[200, false], [300, true]]);
    ($this->product)('All sizes sold out', [[200, false]]);
    ($this->product)('Switched off', attributes: ['is_available' => false]);

    expect(($this->names)(new MenuFilter(onlyAvailable: true)))->toBe(['Available']);
});

it('sorts', function (ProductSort $sort, array $names): void {
    ($this->product)('Middle', [[300, true]], ['sort' => 30, 'created_at' => '2026-01-02']);
    ($this->product)('Cheap', [[900, true], [100, true]], ['sort' => 20, 'created_at' => '2026-01-03']);
    ($this->product)('Dear', [[50, false], [800, true]], ['sort' => 10, 'created_at' => '2026-01-01']);

    expect(($this->names)(new MenuFilter(sort: $sort)))->toBe($names);
})->with([
    'recommended' => [ProductSort::Recommended, ['Dear', 'Cheap', 'Middle']],
    'newest' => [ProductSort::Newest, ['Cheap', 'Middle', 'Dear']],
    'price up' => [ProductSort::PriceLowToHigh, ['Cheap', 'Middle', 'Dear']],
    'price down' => [ProductSort::PriceHighToLow, ['Dear', 'Middle', 'Cheap']],
]);

it('gives one page, and the last one when the number is too high', function (): void {
    foreach (range(1, 5) as $number) {
        ($this->product)("P{$number}", attributes: ['sort' => $number * 10]);
    }

    $last = $this->menu->listing(new MenuFilter(page: 99, perPage: 2));

    expect(($this->names)(new MenuFilter(page: 2, perPage: 2)))->toBe(['P3', 'P4'])
        ->and($last->page)->toBe(3)
        ->and($last->total)->toBe(5)
        ->and($last->items)->toHaveCount(1)
        ->and($this->menu->listing(new MenuFilter(tagSlugs: ['none'], page: 4))->page)->toBe(1);
});

it('knows which products have options', function (): void {
    $with = ($this->product)('With options');
    $off = ($this->product)('With a switched-off group');
    ($this->product)('Plain');
    $groups = app(OptionGroupRepository::class);
    $attachments = app(ProductOptionGroupRepository::class);
    $dips = $groups->save(OptionGroup::create('Dips', 'Dips', SortOrder::first()));
    $inactive = OptionGroup::create('Old', 'Old', SortOrder::fromInt(20));
    $inactive->deactivate();
    $attachments->save(ProductOptionGroup::attach($with->id, $dips, SelectionRules::upTo(3), SortOrder::first()));
    $attachments->save(ProductOptionGroup::attach($off->id, $groups->save($inactive), SelectionRules::upTo(3), SortOrder::first()));

    $cards = $this->menu->listing(new MenuFilter)->items;

    expect(array_map(fn (ProductCard $card): bool => $card->hasOptions, $cards))->toBe([true, false, false])
        ->and(app(StorefrontCatalogQuery::class)->productCardsForCategory($this->pizza->id)[0]->hasOptions)->toBeTrue();

    $groups->delete($dips);
    expect($this->menu->listing(new MenuFilter)->items[0]->hasOptions)->toBeFalse();
});

it('needs the same number of queries for 1 card as for 10 (no N+1)', function (): void {
    $count = function (Closure $read): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $read();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };
    $listing = fn () => $this->menu->listing(new MenuFilter(categoryId: $this->pizza->id, sort: ProductSort::PriceLowToHigh));
    $sections = fn () => $this->menu->sections();

    ($this->product)('First');
    $one = [$count($listing), $count($sections)];

    ProductModel::factory()->count(9)->for($this->pizza)->create();
    CategoryModel::factory()->count(3)->create()->each(fn (CategoryModel $category) => ProductModel::factory()->for($category)->create());

    expect([$count($listing), $count($sections)])->toBe($one);
});
