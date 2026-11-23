<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;

beforeEach(function (): void {
    $this->pizza = CategoryModel::factory()->create(['slug' => 'pizza']);
    $this->product = fn (string $name, array $attributes = []): ProductModel => ProductModel::factory()
        ->for($this->pizza)->create(['name' => $name, 'short_description' => 'Tasty.', 'sort' => 10, ...$attributes]);
});

it('asks for a word when nothing was typed', function (string $uri): void {
    ($this->product)('Margherita');

    $this->get($uri)->assertOk()->assertSee('What are you hungry for?')->assertDontSee('Margherita');
})->with(['/search', '/search?q=', '/search?q=%20%20']);

it('finds names and descriptions, names first', function (): void {
    ($this->product)('Garden Salad', ['short_description' => 'With garlic croutons.', 'sort' => 10]);
    ($this->product)('Garlic Bread', ['sort' => 20]);
    ($this->product)('Cola');
    ($this->product)('Garlic Secret', ['is_published' => false]);

    $this->get('/search?q=garlic')->assertOk()
        ->assertSee('Results for &quot;garlic&quot;', false)
        ->assertSee('2 items')
        ->assertSeeInOrder(['Garlic Bread', 'Garden Salad'])
        ->assertDontSee('Cola')
        ->assertDontSee('Garlic Secret');
});

it('filters and sorts results and keeps the word', function (): void {
    $spicy = TagModel::factory()->create(['name' => 'Spicy', 'slug' => 'spicy']);
    ($this->product)('Hot Pizza')->tags()->attach($spicy);
    ($this->product)('Mild Pizza');

    $this->get('/search?q=pizza&tags[]=spicy')->assertOk()
        ->assertSee('Hot Pizza')->assertDontSee('Mild Pizza')
        ->assertSee('<input type="hidden" name="q" value="pizza">', false)
        ->assertSee('href="'.url('/search?q=pizza').'"', false); // Clear keeps the word
});

it('keeps the word in the page links', function (): void {
    foreach (range(1, 13) as $number) {
        ($this->product)("Pizza {$number}");
    }

    $this->get('/search?q=pizza')->assertSee('13 items')->assertSee('q=pizza&amp;page=2', false);
});

it('says when nothing was found', function (): void {
    $this->get('/search?q=sushi')->assertOk()->assertSee('Nothing found')->assertSee('Browse the menu');
});

it('treats the word as text, never as code or a wildcard', function (): void {
    ($this->product)('Margherita');

    $this->get('/search?q='.urlencode('<script>alert(1)</script>'))->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('Margherita');
    $this->get('/search?q=%25')->assertOk()->assertSee('Nothing found');
    $this->get('/search?q='.urlencode("' OR 1=1 --"))->assertOk()->assertSee('Nothing found');
});

it('never breaks on a strange word', function (string $uri): void {
    $this->get($uri)->assertOk();
})->with(['/search?q[]=x', '/search?q[a][b]=x', '/menu?q[]=x', '/?q[]=x', '/search?q='.str_repeat('a', 500), '/search?q=%FF%FE']);

it('suggests at most five visible products', function (): void {
    foreach (range(1, 7) as $number) {
        ($this->product)("Pizza {$number}", ['sort' => $number * 10]);
    }

    ($this->product)('Pizza Secret', ['is_published' => false, 'sort' => 1]);

    $this->getJson('/search/suggestions?q=pizza')->assertOk()
        ->assertJsonCount(5)
        ->assertJsonPath('0.name', 'Pizza 1')
        ->assertJsonPath('0.url', route('products.show', ProductModel::query()->where('name', 'Pizza 1')->firstOrFail()->slug));
});

it('suggests with price and sold-out state', function (): void {
    ($this->product)('Margherita', ['is_available' => false])->variants()->update(['price' => 45_000]);

    $this->getJson('/search/suggestions?q=marg')->assertOk()
        ->assertJsonPath('0.price', '৳450')
        ->assertJsonPath('0.isSoldOut', true)
        ->assertJsonStructure([['name', 'url', 'image', 'price', 'isSoldOut']]);
});

it('suggests nothing for less than two letters or nonsense', function (string $query): void {
    ($this->product)('Margherita');

    $this->getJson("/search/suggestions?{$query}")->assertOk()->assertExactJson([]);
})->with(['', 'q=', 'q=m', 'q=%20m%20', 'q[]=margherita', 'q=sushi']);

it('limits how often suggestions may be asked for', function (): void {
    foreach (range(1, 60) as $request) {
        $this->getJson('/search/suggestions?q=m')->assertOk();
    }

    $this->getJson('/search/suggestions?q=m')->assertTooManyRequests();
    $this->get('/search?q=m')->assertOk(); // the page itself is not limited
});

it('offers the search field with suggestions on every page', function (): void {
    $this->get('/faq')->assertSee('id="site-search"', false)->assertSee('search\/suggestions', false);
});
