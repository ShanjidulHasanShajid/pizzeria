<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('creates an active size tier without an id', function (): void {
    $tier = SizeTier::create('Personal', Slug::fromString('personal'), SortOrder::first());

    expect($tier->id())->toBeNull()
        ->and($tier->name())->toBe('Personal')
        ->and($tier->slug()->value())->toBe('personal')
        ->and($tier->isActive())->toBeTrue();
});

it('can change its name, slug and position', function (): void {
    $tier = SizeTier::reconstitute(2, 'Medium', Slug::fromString('medium'), SortOrder::fromInt(20), true);

    $tier->rename('Regular');
    $tier->changeSlug(Slug::fromString('regular'));
    $tier->moveTo(SortOrder::fromInt(15));
    $tier->deactivate();

    expect($tier->id())->toBe(2)
        ->and($tier->name())->toBe('Regular')
        ->and($tier->slug()->value())->toBe('regular')
        ->and($tier->sort()->value())->toBe(15)
        ->and($tier->isActive())->toBeFalse();
});

it('rejects an empty name', function (): void {
    expect(fn () => SizeTier::create(' ', Slug::fromString('x'), SortOrder::first()))
        ->toThrow(InvalidValue::class);
});
