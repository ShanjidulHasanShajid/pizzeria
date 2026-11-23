<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Content\Presentation\Support\ContentLabels;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

function bannerWithLink(string $link): ContentItem
{
    return ContentItem::create(ContentType::Banner, ['title' => 'Offer', 'image' => 'a.webp', 'link' => $link], DateRange::always(), true, SortOrder::first());
}

it('keeps every field of its type, trimmed, and ignores others', function (): void {
    $badge = ContentItem::create(ContentType::TrustBadge, ['icon' => 'clock', 'title' => ' Fast ', 'colour' => 'red'], DateRange::always(), true, SortOrder::first());

    expect($badge->values)->toBe(['icon' => 'clock', 'title' => 'Fast', 'text' => '']);
});

it('reports every wrong field at once', function (): void {
    try {
        ContentItem::create(ContentType::HeroSlide, ['subtitle' => str_repeat('x', 256), 'button_link' => 'menu'], DateRange::always(), true, SortOrder::first());
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe(['title', 'subtitle', 'button_link', 'image']);

        return;
    }

    throw new RuntimeException('Wrong fields must be refused.');
});

it('accepts safe links', function (string $link): void {
    expect(bannerWithLink($link)->value('link'))->toBe($link);
})->with(['', '/', '/menu', '/menu?sort=price#top', 'https://example.com/offer']);

it('refuses unsafe links', function (string $link): void {
    bannerWithLink($link);
})->throws(ValidationFailed::class)->with([
    'javascript:alert(1)', '//evil.example', '/\\evil.example', 'http://example.com', 'menu', 'data:text/html,hi', '/menu page',
]);

it('is visible only when switched on and inside its dates', function (string $now, bool $isVisible): void {
    $schedule = DateRange::between(new DateTimeImmutable('2026-12-01 10:00'), new DateTimeImmutable('2026-12-24 22:00'));
    $banner = ContentItem::create(ContentType::Banner, ['title' => 'Offer', 'image' => 'a.webp'], $schedule, true, SortOrder::first());

    expect($banner->isVisibleAt(new DateTimeImmutable($now)))->toBe($isVisible)
        ->and($banner->toggled()->isVisibleAt(new DateTimeImmutable($now)))->toBeFalse();
})->with([
    'before' => ['2026-12-01 09:59', false],
    'at the start' => ['2026-12-01 10:00', true],
    'at the end' => ['2026-12-24 22:00', true],
    'after' => ['2026-12-24 22:01', false],
]);

it('ignores dates for types that are not scheduled', function (): void {
    $past = DateRange::between(null, new DateTimeImmutable('2020-01-01'));
    $photo = ContentItem::create(ContentType::GalleryImage, ['image' => 'a.webp'], $past, true, SortOrder::first());

    expect($photo->isVisibleAt(new DateTimeImmutable('2026-12-01')))->toBeTrue();
});

it('makes a new object when it changes and keeps fields that are not given', function (): void {
    $old = bannerWithLink('/menu');
    $new = $old->change(['title' => 'New'], DateRange::always(), false);

    expect($old->value('title'))->toBe('Offer')
        ->and($new->value('title'))->toBe('New')
        ->and($new->value('link'))->toBe('/menu')
        ->and($new->isActive)->toBeFalse()
        ->and($new->imagePaths())->toBe(['a.webp']);
});

it('has a label for every field of every type', function (): void {
    foreach (ContentType::cases() as $type) {
        foreach ($type->fields() as $field) {
            expect(ContentLabels::for($field->name)['label'])->not->toBe($field->name);
        }
    }
});
