<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\ValueObjects\PricedLine;
use App\Modules\Customization\Domain\ValueObjects\PricedOption;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

it('adds what the options cost to the base price and multiplies by the quantity', function (): void {
    $line = PricedLine::fromParts(10, 'Margherita', 103, 'Large', Money::fromTaka('1250'), [
        new PricedOption(1, 'Crust', 13, 'Stuffed', Money::fromTaka('200'), Money::fromTaka('200')),
        new PricedOption(2, 'Extra toppings', 24, 'Jalapeños', Money::fromTaka('50'), Money::zero()),
    ], Quantity::of(2));

    expect($line->unitPrice()->poisha())->toBe(145_000)
        ->and($line->lineTotal()->poisha())->toBe(290_000)
        ->and($line->options()[0]->isFreePick())->toBeFalse()
        ->and($line->options()[1]->isFreePick())->toBeTrue();
});
