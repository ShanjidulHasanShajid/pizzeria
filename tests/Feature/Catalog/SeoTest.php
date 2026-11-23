<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->drinks = CategoryModel::factory()->create(['name' => 'Drinks', 'slug' => 'drinks', 'description' => 'Cold and hot.']);
    $this->coffee = CategoryModel::factory()->childOf($this->drinks)->create(['name' => 'Coffee', 'slug' => 'coffee', 'description' => null]);
    $this->latte = ProductModel::factory()->for($this->coffee)->create(['name' => 'Latte', 'slug' => 'latte', 'short_description' => 'With milk.']);
    $this->tag = TagModel::factory()->create(['name' => 'Coffee', 'slug' => 'coffee']);
    $this->latte->tags()->attach($this->tag);
    $this->read = function (string $uri, string $pattern): ?string {
        preg_match($pattern, (string) $this->get($uri)->assertOk()->getContent(), $found);

        return isset($found[1]) ? html_entity_decode($found[1]) : null;
    };
    $this->canonical = fn (string $uri): ?string => ($this->read)($uri, '#<link rel="canonical" href="([^"]*)"#');
});

it('gives every page its own title and description', function (): void {
    $titles = [];
    $descriptions = [];

    foreach (['/', '/menu', '/menu/drinks', '/menu/drinks/coffee', '/product/latte', '/tags/coffee', '/search', '/search?q=latte'] as $uri) {
        $titles[] = ($this->read)($uri, '#<title>(.*?)</title>#s');
        $descriptions[$uri] = ($this->read)($uri, '#<meta name="description" content="([^"]*)"#');
    }

    expect(array_unique($titles))->toHaveCount(8)
        ->and(array_filter($descriptions, fn (?string $text): bool => (string) $text === ''))->toBe([]);

    unset($descriptions['/search?q=latte']); // the search page says the same for every word
    expect(array_unique($descriptions))->toHaveCount(7);
});

it('has one canonical address per page', function (string $uri, string $canonical): void {
    expect(($this->canonical)($uri))->toBe(url($canonical));
})->with([
    ['/', '/'],
    ['/menu', '/menu'],
    ['/menu?sort=newest', '/menu'],
    ['/menu/drinks?sort=price-asc&page=1', '/menu/drinks'],
    ['/menu/drinks/coffee?utm_source=newsletter', '/menu/drinks/coffee'],
    ['/product/latte?ref=x', '/product/latte'],
    ['/tags/coffee', '/tags/coffee'],
]);

it('treats page 2 of a list as a page of its own', function (): void {
    ProductModel::factory()->count(13)->for($this->drinks)->create();

    expect(($this->canonical)('/menu/drinks?page=2'))->toBe(url('/menu/drinks').'?page=2')
        ->and(($this->read)('/menu/drinks?page=2', '#<title>(.*?)</title>#s'))->toContain('page 2')
        ->and(($this->canonical)('/menu/drinks?page=2&sort=newest'))->toBe(url('/menu/drinks'));
});

it('hides search results, accounts and unfinished pages from search engines', function (string $uri): void {
    $this->get($uri)->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false)->assertDontSee('rel="canonical"', false);
})->with(['/search', '/search?q=latte', '/login', '/register', '/forgot-password', '/cart', '/faq', '/build-your-pizza']);

it('hides the account page too', function (): void {
    $this->actingAs(User::factory()->create())->get('/account')->assertOk()->assertSee('noindex, follow');
});

it('does not hide the menu', function (string $uri): void {
    $this->get($uri)->assertOk()->assertDontSee('noindex');
})->with(['/', '/menu', '/menu/drinks', '/menu/drinks/coffee', '/product/latte', '/tags/coffee']);

it('offers a preview for shared links, with the photo of the product', function (): void {
    ProductImageModel::factory()->for($this->latte)->main()->create(['path' => 'products/latte.webp']);

    $this->get('/product/latte')
        ->assertSee('<meta property="og:title" content="Latte', false)
        ->assertSee('<meta property="og:description" content="With milk.">', false)
        ->assertSee('<meta property="og:url" content="'.url('/product/latte').'">', false)
        ->assertSee('summary_large_image', false);

    expect(($this->read)('/product/latte', '#<meta property="og:image" content="([^"]*)"#'))->toStartWith('http')->toEndWith('products/latte.webp');
});

it('uses the logo as preview image of other pages, if there is one', function (): void {
    $this->get('/menu')->assertDontSee('og:image', false);

    app(SettingsRepository::class)->save(['logo' => 'settings/logo.webp']);

    expect(($this->read)('/menu', '#<meta property="og:image" content="([^"]*)"#'))->toEndWith('settings/logo.webp');
});

it('escapes names in the head', function (): void {
    $this->latte->update(['name' => 'Latte "Special" <b>', 'short_description' => '"><script>alert(1)</script>']);

    $this->get('/product/latte')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;b&gt;', false);
});

it('lists visible categories and products in the sitemap', function (): void {
    $closed = CategoryModel::factory()->inactive()->create(['slug' => 'closed']);
    ProductModel::factory()->for($closed)->create(['slug' => 'inside-closed']);
    ProductModel::factory()->for($this->drinks)->unpublished()->create(['slug' => 'secret']);
    ProductModel::factory()->for($this->drinks)->create(['slug' => 'deleted'])->delete();
    ProductModel::factory()->for($this->drinks)->create(['slug' => 'sold-out', 'is_available' => false]);
    $this->latte->forceFill(['updated_at' => '2026-11-05 10:00:00'])->saveQuietly();

    $response = $this->get('/sitemap.xml')->assertOk();
    $xml = simplexml_load_string((string) $response->getContent());
    $addresses = [];

    foreach ($xml->url as $url) {
        $addresses[] = (string) $url->loc;
    }

    expect($response->headers->get('Content-Type'))->toContain('application/xml')
        ->and($addresses)->toEqualCanonicalizing(array_map(url(...), ['/', '/menu', '/menu/drinks', '/menu/drinks/coffee', '/product/latte', '/product/sold-out']))
        ->and((string) $response->getContent())->toContain('<lastmod>2026-11-05</lastmod>');
});

it('lists only pages that exist and may be found', function (): void {
    $xml = simplexml_load_string((string) $this->get('/sitemap.xml')->getContent());

    foreach ($xml->url as $url) {
        $this->get((string) $url->loc)->assertOk()->assertDontSee('noindex');
    }
});

it('needs the same number of queries on every listing page, however long it is (no N+1)', function (string $uri): void {
    $count = function () use ($uri): int {
        $this->get($uri)->assertOk(); // fills the caches
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($uri)->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };
    ProductModel::factory()->for($this->drinks)->create(['name' => 'Latte Cold'])->tags()->attach($this->tag);
    $few = $count();

    ProductModel::factory()->count(8)->for($this->drinks)->create(['name' => 'Latte Hot'])->each(fn (ProductModel $product) => $product->tags()->attach($this->tag));

    expect($count())->toBe($few)->and($few)->toBeLessThanOrEqual(30);
})->with(['/menu', '/menu?sort=price-asc', '/menu/drinks', '/menu/drinks/coffee', '/tags/coffee', '/search?q=latte', '/search/suggestions?q=latte', '/product/latte', '/sitemap.xml']);
