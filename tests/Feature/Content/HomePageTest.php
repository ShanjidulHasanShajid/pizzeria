<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->saveItem = fn (ContentType $type, array $values, ?DateRange $schedule = null, bool $isActive = true): int => app(ContentItemRepository::class)->save(
        ContentItem::create($type, $values, $schedule ?? DateRange::always(), $isActive, SortOrder::first()),
    );
    $this->saveLayout = fn (array $tabs, array $rows = []) => app(HomeLayoutRepository::class)->save(new HomeLayout($tabs, $rows));
    $this->product = fn (CategoryModel $category, string $name, int $sort = 10, array $attributes = []): ProductModel => ProductModel::factory()
        ->for($category)->create(['name' => $name, 'sort' => $sort, ...$attributes]);
});

it('works on an empty database and leaves every section out', function (): void {
    app(SettingsRepository::class)->save(['builder_title' => '']);

    $this->get('/')->assertOk()
        ->assertDontSee('aria-roledescription="carousel"', false)
        ->assertDontSee('Why order from us')
        ->assertDontSee('aria-label="Promotions"', false)
        ->assertDontSee('Hot right now')
        ->assertDontSee('role="tablist"', false)
        ->assertDontSee('Start building')
        ->assertDontSee('Find a branch')
        ->assertDontSee('Photo viewer');
});

it('shows the content of the admin', function (): void {
    ($this->saveItem)(ContentType::HeroSlide, ['title' => 'Winter is hot', 'subtitle' => 'Come in', 'button_text' => 'Order now', 'button_link' => '/menu', 'image' => 'content/hero-slides/wide.webp', 'mobile_image' => 'content/hero-slides/tall.webp']);
    ($this->saveItem)(ContentType::TrustBadge, ['icon' => 'clock', 'title' => 'In 30 minutes', 'text' => 'Or sooner']);
    ($this->saveItem)(ContentType::Banner, ['title' => 'Pasta Tuesday', 'link' => 'https://example.com/pasta', 'image' => 'content/banners/pasta.webp']);
    ($this->saveItem)(ContentType::GalleryImage, ['image' => 'content/gallery/oven.webp', 'caption' => 'Our oven']);
    ($this->saveItem)(ContentType::GalleryImage, ['image' => 'content/gallery/hall.webp'], null, false);
    app(SettingsRepository::class)->save(['builder_title' => 'Your own pizza', 'builder_text' => 'Pick everything.', 'opening_hours_text' => 'Daily from 11', 'combos_strip_title' => 'Deals for you']);

    $this->get('/')->assertOk()
        ->assertSeeInOrder(['Winter is hot', 'In 30 minutes', 'Pasta Tuesday', 'Your own pizza', 'Daily from 11', 'Our oven'])
        ->assertSee('wide.webp')
        ->assertSee('tall.webp')
        ->assertSee('href="'.url('/menu').'"', false)
        ->assertSee('href="https://example.com/pasta"', false)
        ->assertSee('oven.webp')
        ->assertDontSee('hall.webp')
        ->assertDontSee('Deals for you'); // comes in Phase 15
});

it('shows a slide without a button when the link is missing', function (): void {
    ($this->saveItem)(ContentType::HeroSlide, ['title' => 'Only words', 'button_text' => 'Click me', 'image' => 'a.webp']);

    $this->get('/')->assertSee('Only words')->assertDontSee('Click me')->assertDontSee('Next slide');
});

it('shows scheduled content only inside its dates', function (): void {
    $clock = FrozenClock::at('2026-11-30 23:59');
    $this->app->instance(Clock::class, $clock);
    ($this->saveItem)(ContentType::Banner, ['title' => 'December offer', 'image' => 'a.webp'], DateRange::between(new DateTimeImmutable('2026-12-01 00:00'), null));

    $this->get('/')->assertDontSee('December offer');
    $clock->advance('+1 minute');
    $this->get('/')->assertSee('December offer');
});

it('shows as many hot products as the setting says, and no hidden ones', function (): void {
    $pizza = CategoryModel::factory()->create();
    ($this->product)($pizza, 'Hot One', 10, ['is_hot' => true]);
    ($this->product)($pizza, 'Hot Hidden', 20, ['is_hot' => true, 'is_published' => false]);
    ($this->product)($pizza, 'Hot Two', 30, ['is_hot' => true]);
    ($this->product)($pizza, 'Hot Three', 40, ['is_hot' => true]);
    ($this->product)($pizza, 'Cold One', 5);
    app(SettingsRepository::class)->save(['hot_items_limit' => '2']);

    $this->get('/')->assertSeeInOrder(['Hot right now', 'Hot One', 'Hot Two'])
        ->assertDontSee('Hot Three')->assertDontSee('Hot Hidden')->assertDontSee('Cold One');
});

it('shows the chosen tabs and rows with picked products first', function (): void {
    $pizza = CategoryModel::factory()->create(['name' => 'Pizza Corner']);
    $pasta = CategoryModel::factory()->create(['name' => 'Pasta Corner']);
    ($this->product)($pizza, 'Slice A', 10);
    ($this->product)($pizza, 'Slice B', 20);
    ($this->product)($pizza, 'Slice C', 30);
    $picked = ($this->product)($pizza, 'Slice D', 40);
    ($this->product)($pasta, 'Pasta A');
    ($this->saveLayout)([$pasta->id], [new HomeRow($pizza->id, [$picked->id])]);

    $this->get('/')->assertSeeInOrder(['Pasta Corner', 'Pasta A', 'Pizza Corner', 'Slice D', 'Slice A', 'Slice B'])
        ->assertDontSee('Slice C');
});

it('leaves out tabs and rows of hidden or empty categories', function (): void {
    $hidden = CategoryModel::factory()->inactive()->create(['name' => 'Hidden Corner']);
    $empty = CategoryModel::factory()->hiddenFromMegaMenu()->create(['name' => 'Empty Corner']);
    ($this->product)($hidden, 'Nobody sees me');
    ($this->saveLayout)([$hidden->id, $empty->id], [new HomeRow($hidden->id, [])]);

    $this->get('/')->assertOk()->assertDontSee('Hidden Corner')->assertDontSee('Empty Corner')->assertDontSee('Nobody sees me');
});

it('needs the same number of queries for much content as for little (no N+1)', function (): void {
    $count = function (): int {
        $this->get('/')->assertOk(); // fills the caches
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/')->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };
    $categories = CategoryModel::factory()->count(6)->create();

    foreach ($categories as $category) {
        ($this->product)($category, 'First', 10, ['is_hot' => true]);
    }

    $ids = $categories->pluck('id')->all();
    ($this->saveLayout)([$ids[0]], [new HomeRow($ids[0], [])]);
    $little = $count();

    foreach ($categories as $category) {
        CategoryModel::factory()->childOf($category)->create();
        ProductModel::factory()->count(4)->for($category)->create(['is_hot' => true]);
    }

    ($this->saveLayout)($ids, array_map(fn (int $id): HomeRow => new HomeRow($id, []), array_slice($ids, 0, 3)));
    ($this->saveItem)(ContentType::HeroSlide, ['title' => 'A', 'image' => 'a.webp']);
    ($this->saveItem)(ContentType::Banner, ['title' => 'B', 'image' => 'b.webp']);

    expect($count())->toBe($little)
        ->and($little)->toBeLessThanOrEqual(30);
});
