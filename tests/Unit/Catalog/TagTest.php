<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('creates an active tag with a type and an icon', function (): void {
    $tag = Tag::create('Spicy', Slug::fromString('spicy'), TagType::Flavour, SortOrder::first(), 'fire');

    expect($tag->id())->toBeNull()
        ->and($tag->type())->toBe(TagType::Flavour)
        ->and($tag->icon())->toBe('fire')
        ->and($tag->isActive())->toBeTrue();
});

it('treats an empty icon as no icon', function (): void {
    $tag = Tag::create('Halal', Slug::fromString('halal'), TagType::Dietary, SortOrder::first(), '  ');

    expect($tag->icon())->toBeNull();
});

it('can change its type and icon', function (): void {
    $tag = Tag::create('Nuts', Slug::fromString('nuts'), TagType::Dietary, SortOrder::first());

    $tag->changeType(TagType::Allergen);
    $tag->changeIcon('exclamation-triangle');

    expect($tag->type())->toBe(TagType::Allergen)
        ->and($tag->icon())->toBe('exclamation-triangle');
});

it('rejects an icon name with spaces or capitals', function (string $icon): void {
    expect(fn () => Tag::create('Veg', Slug::fromString('veg'), TagType::Dietary, SortOrder::first(), $icon))
        ->toThrow(InvalidValue::class);
})->with(['Fire', 'hot pepper', '<svg>']);
