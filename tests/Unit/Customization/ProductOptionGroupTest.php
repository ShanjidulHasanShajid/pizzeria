<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('attaches a group to a product with rules, and changes them', function (): void {
    $crust = ProductOptionGroup::attach(productId: 10, optionGroupId: 1, rules: SelectionRules::exactlyOne(), sort: SortOrder::first());

    $crust->changeRules(SelectionRules::upTo(1));
    $crust->moveTo(SortOrder::fromInt(30));

    expect($crust->id())->toBeNull()
        ->and($crust->productId())->toBe(10)
        ->and($crust->optionGroupId())->toBe(1)
        ->and($crust->rules()->isRequired())->toBeFalse()
        ->and($crust->sort()->value())->toBe(30);
});

it('needs a real product and a real group', function (): void {
    expect(fn () => ProductOptionGroup::attach(0, 1, SelectionRules::exactlyOne(), SortOrder::first()))->toThrow(InvalidValue::class);
    expect(fn () => ProductOptionGroup::attach(10, 0, SelectionRules::exactlyOne(), SortOrder::first()))->toThrow(InvalidValue::class);
});
