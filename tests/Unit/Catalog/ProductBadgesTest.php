<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;

it('starts with no badges', function (): void {
    $badges = ProductBadges::none();

    expect($badges->isNew())->toBeFalse()
        ->and($badges->isHot())->toBeFalse()
        ->and($badges->hasAny())->toBeFalse();
});

it('returns a new object when a badge changes and keeps the old one', function (): void {
    $before = ProductBadges::none();
    $after = $before->withHot(true);

    expect($after->isHot())->toBeTrue()
        ->and($after->isNew())->toBeFalse()
        ->and($before->isHot())->toBeFalse();
});

it('compares by value', function (): void {
    expect(ProductBadges::of(true, false)->equals(ProductBadges::none()->withNew(true)))->toBeTrue()
        ->and(ProductBadges::of(true, true)->equals(ProductBadges::of(true, false)))->toBeFalse();
});
