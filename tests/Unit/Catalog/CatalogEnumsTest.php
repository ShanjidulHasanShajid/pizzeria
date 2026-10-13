<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Enums\TagType;

it('stores the values the database expects', function (): void {
    expect(array_column(ProductType::cases(), 'value'))->toBe(['standard', 'builder', 'combo'])
        ->and(array_column(LeadTimeClass::cases(), 'value'))->toBe(['made_to_order', 'same_day', 'next_day'])
        ->and(array_column(TagType::cases(), 'value'))->toBe(['dietary', 'allergen', 'flavour']);
});

it('allows only one variant on a combo', function (): void {
    expect(ProductType::Combo->allowsOnlyOneVariant())->toBeTrue()
        ->and(ProductType::Standard->allowsOnlyOneVariant())->toBeFalse()
        ->and(ProductType::Builder->allowsOnlyOneVariant())->toBeFalse();
});

it('asks for a size tier on every builder variant', function (): void {
    expect(ProductType::Builder->needsSizeTierOnEveryVariant())->toBeTrue()
        ->and(ProductType::Standard->needsSizeTierOnEveryVariant())->toBeFalse()
        ->and(ProductType::Combo->needsSizeTierOnEveryVariant())->toBeFalse();
});

it('has a label for every case', function (): void {
    $cases = [...ProductType::cases(), ...LeadTimeClass::cases(), ...TagType::cases()];

    foreach ($cases as $case) {
        expect($case->label())->not->toBe('');
    }
});
