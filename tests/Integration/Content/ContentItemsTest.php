<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\ContentItemView;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedContentItemRepository;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    foreach (ContentType::cases() as $type) {
        Cache::forget(CachedContentItemRepository::cacheKey($type));
    }

    $this->items = app(ContentItemRepository::class);
    $this->saveBanner = fn (string $title, int $sort = 10, ?DateRange $schedule = null, bool $isActive = true): int => $this->items->save(
        ContentItem::create(ContentType::Banner, ['title' => $title, 'image' => 'a.webp'], $schedule ?? DateRange::always(), $isActive, SortOrder::fromInt($sort)),
    );
});

it('saves and loads an item of every type', function (ContentType $type, array $values): void {
    $id = $this->items->save(ContentItem::create($type, $values, DateRange::always(), true, SortOrder::fromInt(30)));

    $item = $this->items->find($type, $id);

    foreach ($values as $field => $value) {
        expect($item->value($field))->toBe($value);
    }

    expect($item->isActive)->toBeTrue()
        ->and($item->sort->value())->toBe(30)
        ->and($this->items->all($type)[0]->values)->toBe($item->values);
})->with([
    'hero slide' => [ContentType::HeroSlide, ['title' => 'Hello', 'subtitle' => 'Fresh', 'button_text' => 'Order', 'button_link' => '/menu', 'image' => 'a.webp', 'mobile_image' => 'b.webp']],
    'banner' => [ContentType::Banner, ['title' => 'Offer', 'link' => 'https://example.com', 'image' => 'a.webp']],
    'trust badge' => [ContentType::TrustBadge, ['icon' => 'clock', 'title' => 'Fast', 'text' => 'In 30 minutes']],
    'gallery image' => [ContentType::GalleryImage, ['image' => 'a.webp', 'caption' => 'Our oven']],
]);

it('updates an item in place, with its dates to the minute', function (): void {
    $id = ($this->saveBanner)('Offer');
    $schedule = DateRange::between(new DateTimeImmutable('2026-12-01 10:30'), new DateTimeImmutable('2026-12-24 22:00'));

    $this->items->save($this->items->find(ContentType::Banner, $id)->change(['title' => 'New'], $schedule, false));

    $item = $this->items->find(ContentType::Banner, $id);

    expect($item->value('title'))->toBe('New')
        ->and($item->isActive)->toBeFalse()
        ->and($item->schedule->start()->format('Y-m-d H:i'))->toBe('2026-12-01 10:30')
        ->and($item->schedule->end()->format('Y-m-d H:i'))->toBe('2026-12-24 22:00');
    $this->assertDatabaseCount('banners', 1);
});

it('moves an item to the Trash and back, and gives the next position', function (): void {
    $id = ($this->saveBanner)('Offer', 20);
    expect($this->items->nextSortOrder(ContentType::Banner)->value())->toBe(30);

    $this->items->delete(ContentType::Banner, $id);
    $this->assertSoftDeleted('banners', ['id' => $id]);
    expect($this->items->find(ContentType::Banner, $id))->toBeNull()
        ->and($this->items->nextSortOrder(ContentType::Banner)->value())->toBe(10);

    $this->items->restore(ContentType::Banner, $id);
    expect($this->items->all(ContentType::Banner))->toHaveCount(1);
    expect(fn () => $this->items->restore(ContentType::Banner, $id))->toThrow(ModelNotFoundException::class);
});

it('clears the cached list after every change', function (): void {
    $titles = fn (): array => array_map(fn (ContentItem $item): string => $item->value('title'), $this->items->all(ContentType::Banner));

    expect($titles())->toBe([]);
    $id = ($this->saveBanner)('Offer');
    expect($titles())->toBe(['Offer']);
    $this->items->save($this->items->find(ContentType::Banner, $id)->change(['title' => 'New'], DateRange::always(), true));
    expect($titles())->toBe(['New']);
    $this->items->delete(ContentType::Banner, $id);
    expect($titles())->toBe([]);
    $this->items->restore(ContentType::Banner, $id);
    expect($titles())->toBe(['New']);
});

it('reads the database once and then the cache', function (): void {
    ($this->saveBanner)('Offer', schedule: DateRange::between(new DateTimeImmutable('2026-12-01 10:30'), null));
    $this->items->all(ContentType::Banner);

    DB::enableQueryLog();
    $item = $this->items->all(ContentType::Banner)[0];

    expect($item->schedule->start()->format('Y-m-d H:i'))->toBe('2026-12-01 10:30')
        ->and(array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'banners')))->toBe([]);
});

it('shows a scheduled item only inside its dates, also from the cache', function (): void {
    $clock = FrozenClock::at('2026-11-30 12:00');
    $this->app->instance(Clock::class, $clock);
    $query = app(ContentQuery::class);
    ($this->saveBanner)('December', 10, DateRange::between(new DateTimeImmutable('2026-12-01 00:00'), new DateTimeImmutable('2026-12-31 23:59')));
    ($this->saveBanner)('Always', 20);
    ($this->saveBanner)('Off', 30, isActive: false);

    $visible = fn (): array => array_map(fn (ContentItemView $item): string => $item->value('title'), $query->visible(ContentType::Banner));
    $states = fn (): array => array_map(fn (ContentItemView $item): string => $item->state, $query->table(ContentType::Banner));

    expect($visible())->toBe(['Always'])
        ->and($states())->toBe(['scheduled', 'visible', 'inactive']);

    $clock->advance('+2 days');
    expect($visible())->toBe(['December', 'Always']);

    $clock->advance('+40 days');
    expect($visible())->toBe(['Always'])
        ->and($states())->toBe(['expired', 'visible', 'inactive']);
});
