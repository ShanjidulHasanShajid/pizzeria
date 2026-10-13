<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('creates an available, non-default variant with a price in poisha', function (): void {
    $variant = Variant::create('Large', Money::fromTaka('1250'), SortOrder::first(), sizeTierId: 3);

    expect($variant->id())->toBeNull()
        ->and($variant->label())->toBe('Large')
        ->and($variant->price()->poisha())->toBe(125_000)
        ->and($variant->sizeTierId())->toBe(3)
        ->and($variant->isDefault())->toBeFalse()
        ->and($variant->isAvailable())->toBeTrue();
});

it('allows a variant without a size tier', function (): void {
    $variant = Variant::create('6 pcs', Money::fromTaka('390'), SortOrder::first());

    expect($variant->sizeTierId())->toBeNull();
});

it('changes price, label, size tier and availability', function (): void {
    $variant = Variant::reconstitute(5, 'Medium', Money::fromTaka('850'), 2, true, true, SortOrder::fromInt(20));

    $variant->changePrice(Money::fromTaka('899.50'));
    $variant->relabel('Regular');
    $variant->linkToSizeTier(null);
    $variant->markSoldOut();

    expect($variant->price()->poisha())->toBe(89_950)
        ->and($variant->label())->toBe('Regular')
        ->and($variant->sizeTierId())->toBeNull()
        ->and($variant->isAvailable())->toBeFalse();
});

it('can be marked and unmarked as the default', function (): void {
    $variant = Variant::create('Large', Money::fromTaka('1250'), SortOrder::first());

    $variant->markAsDefault();
    expect($variant->isDefault())->toBeTrue();

    $variant->unmarkAsDefault();
    expect($variant->isDefault())->toBeFalse();
});

it('compares labels without case and outer spaces', function (): void {
    $variant = Variant::create('Large', Money::fromTaka('1250'), SortOrder::first());

    expect($variant->hasLabel(' large '))->toBeTrue()
        ->and($variant->hasLabel('Medium'))->toBeFalse();
});

it('rejects an empty label and an invalid size tier id', function (): void {
    expect(fn () => Variant::create(' ', Money::fromTaka('100'), SortOrder::first()))->toThrow(InvalidValue::class)
        ->and(fn () => Variant::create('Large', Money::fromTaka('100'), SortOrder::first(), sizeTierId: 0))
        ->toThrow(InvalidValue::class);
});
