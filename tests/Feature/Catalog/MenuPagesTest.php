<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;

beforeEach(function (): void {
    $this->pizza = CategoryModel::factory()->create(['name' => 'Pizza Corner', 'slug' => 'pizza', 'description' => 'Stone baked.', 'sort' => 10]);
    $this->drinks = CategoryModel::factory()->create(['name' => 'Cold Drinks', 'slug' => 'drinks', 'sort' => 20]);
    $this->coffee = CategoryModel::factory()->childOf($this->drinks)->create(['name' => 'Iced Coffee', 'slug' => 'coffee']);
    $this->product = fn (string $name, ?CategoryModel $category = null, array $attributes = []): ProductModel => ProductModel::factory()
        ->for($category ?? $this->pizza)->create(['name' => $name, ...$attributes]);
});

it('shows the menu grouped by category', function (): void {
    ($this->product)('Margherita');
    ($this->product)('Latte', $this->coffee);
    ($this->product)('Secret Pizza', null, ['is_published' => false]);
    CategoryModel::factory()->inactive()->create(['name' => 'Closed Corner']);

    $this->get('/menu')->assertOk()
        ->assertSeeInOrder(['id="pizza"', 'Margherita', 'id="drinks"', 'Latte'], false)
        ->assertSee('href="'.route('categories.show', 'pizza').'"', false)
        ->assertDontSee('Secret Pizza')
        ->assertDontSee('Closed Corner');
});

it('shows a flat list when the menu is filtered', function (): void {
    ($this->product)('Margherita');
    ($this->product)('Latte', $this->coffee);

    $this->get('/menu?sort=newest')->assertOk()
        ->assertSee('Margherita')->assertSee('Latte')->assertSee('2 items')
        ->assertDontSee('id="pizza"', false);
});

it('shows a category with its sub-categories and products', function (): void {
    ($this->product)('Cola', $this->drinks);
    ($this->product)('Latte', $this->coffee);
    ($this->product)('Margherita');

    $this->get('/menu/drinks')->assertOk()
        ->assertSee('<h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">Cold Drinks</h1>', false)
        ->assertSee('href="'.url('/menu/drinks/coffee').'"', false)
        ->assertSee('Cola')->assertSee('Latte')->assertDontSee('Margherita');
    $this->get('/menu/pizza')->assertSee('Stone baked.')->assertSee('<title>Pizza Corner', false);
});

it('shows a sub-category below its parent only', function (): void {
    ($this->product)('Cola', $this->drinks);
    ($this->product)('Latte', $this->coffee);

    $this->get('/menu/drinks/coffee')->assertOk()
        ->assertSee('Latte')->assertDontSee('Cola')
        ->assertSee('href="'.url('/menu/drinks').'"', false);
    $this->get('/menu/coffee')->assertStatus(301)->assertRedirect('/menu/drinks/coffee');
    $this->get('/menu/pizza/coffee')->assertNotFound();
    $this->get('/menu/drinks/pizza')->assertNotFound();
});

it('answers 404 for hidden and unknown categories and tags', function (string $uri): void {
    $closed = CategoryModel::factory()->inactive()->create(['slug' => 'closed']);
    CategoryModel::factory()->childOf($closed)->create(['slug' => 'inside']);
    CategoryModel::factory()->create(['slug' => 'deleted'])->delete();
    TagModel::factory()->create(['slug' => 'old', 'is_active' => false]);
    TagModel::factory()->create(['slug' => 'gone'])->delete();

    $this->get($uri)->assertNotFound();
})->with(['/menu/unknown', '/menu/closed', '/menu/closed/inside', '/menu/deleted', '/menu/Pizza!', '/tags/unknown', '/tags/old', '/tags/gone']);

it('shows a tag page', function (): void {
    $veg = TagModel::factory()->create(['name' => 'Vegetarian', 'slug' => 'vegetarian', 'type' => TagType::Dietary]);
    TagModel::factory()->create(['name' => 'Spicy', 'slug' => 'spicy', 'type' => TagType::Flavour]);
    ($this->product)('Margherita')->tags()->attach($veg);
    ($this->product)('Beef Pizza');

    $this->get('/tags/vegetarian')->assertOk()
        ->assertSee('<title>Vegetarian', false)
        ->assertSee('Margherita')->assertDontSee('Beef Pizza')
        ->assertSee('value="spicy"', false)
        ->assertDontSee('value="vegetarian"', false); // the tag of the page is no choice
});

it('filters by tag, price and availability, and remembers the choice', function (): void {
    $spicy = TagModel::factory()->create(['name' => 'Spicy', 'slug' => 'spicy', 'type' => TagType::Flavour]);
    $hot = ($this->product)('Hot Pizza');
    $hot->tags()->attach($spicy);
    $hot->variants()->update(['price' => 45_000]);
    $soldOut = ($this->product)('Gone Pizza', null, ['is_available' => false]);
    $soldOut->tags()->attach($spicy);
    $soldOut->variants()->update(['price' => 45_000]);
    ($this->product)('Mild Pizza')->variants()->update(['price' => 45_000]);

    $this->get('/menu/pizza?tags[]=spicy&min=400&max=500&available=1&sort=price-desc')->assertOk()
        ->assertSee('Hot Pizza')->assertDontSee('Gone Pizza')->assertDontSee('Mild Pizza')
        ->assertSee('1 item')
        ->assertSee('value="400"', false)
        ->assertSee('value="spicy" checked', false)
        ->assertSee('value="price-desc" selected', false)
        ->assertSee('Clear');
});

it('keeps the filter in the page links', function (): void {
    foreach (range(1, 13) as $number) {
        ($this->product)('Pizza '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), null, ['sort' => $number * 10]);
    }

    $this->get('/menu/pizza?sort=price-asc&tags[]=')->assertOk()
        ->assertSee('13 items')
        ->assertSee('sort=price-asc&amp;', false)
        ->assertSee('page=2', false);
    $this->get('/menu/pizza?page=2')->assertOk()->assertSee('Pizza 13')->assertDontSee('Pizza 01');
    $this->get('/menu/pizza?page=999')->assertOk()->assertSee('Pizza 13');
});

it('never breaks on a strange address', function (string $query): void {
    ($this->product)('Margherita');

    $this->get("/menu/pizza?{$query}")->assertOk()->assertSee('Margherita');
})->with([
    'sort[]=price-asc', 'sort=drop', 'min=abc', 'min=-5', 'min[]=1', 'max=99999999999', 'max=',
    'tags=spicy', 'tags[][]=x', 'tags[]=%27%20OR%201', 'page=0', 'page=-1', 'page=x', 'page[]=2', 'available=maybe',
]);

it('says why a page is empty', function (): void {
    $this->get('/menu')->assertOk()->assertSee('The menu is being prepared');
    $this->get('/menu/pizza')->assertOk()->assertSee('Nothing here yet')->assertSee('Browse the menu');

    ($this->product)('Margherita');
    $this->get('/menu/pizza?tags[]=unknown')->assertOk()->assertSee('Nothing matches your filter')->assertSee('Clear the filter');
});

it('draws the cards with real data', function (): void {
    $nuts = TagModel::factory()->create(['name' => 'Contains Nuts', 'type' => TagType::Allergen, 'icon' => 'exclamation-triangle']);
    $rich = ($this->product)('Rich Pizza', null, ['is_new' => true, 'is_hot' => true, 'lead_time_class' => LeadTimeClass::NextDay, 'lead_time_minutes' => 1440, 'sort' => 10]);
    $rich->tags()->attach($nuts);
    $rich->variants()->firstOrFail()->update(['label' => 'Personal', 'price' => 45_000, 'sort' => 10]);
    $large = $rich->variants()->firstOrFail()->replicate();
    $large->fill(['label' => 'Large', 'price' => 125_000, 'is_default' => false, 'sort' => 20]);
    $large->save();
    ($this->product)('Gone Pizza', null, ['is_available' => false, 'sort' => 20]);
    ($this->product)('Combo', null, ['type' => ProductType::Combo, 'sort' => 30]);
    ($this->product)('Plain Bread', null, ['sort' => 40]);

    $this->get('/menu/pizza')->assertOk()->assertSeeInOrder([
        'New', 'Hot', 'Rich Pizza', 'Contains Nuts', 'Personal', 'Large', 'From', '৳450', 'Choose options',
        'Sold out', 'Gone Pizza', 'disabled',
        'Combo', 'Customize combo',
        'Plain Bread', 'Add to cart',
    ]);
});

it('links the mega menu to sub-category pages', function (): void {
    $this->get('/menu')->assertSee('href="'.url('/menu/drinks/coffee').'"', false);
});
