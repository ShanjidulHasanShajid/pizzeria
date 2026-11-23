<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\OptionView;
use App\Modules\Shared\Domain\ValueObjects\Money;

it('knows its price for a size', function (): void {
    $flat = new OptionView(1, 'Corn', null, true, true, Money::fromTaka(50), []);
    $perSize = new OptionView(2, 'Stuffed', null, false, true, null, [7 => Money::fromTaka(200)]);

    expect($flat->priceFor(7)?->poisha())->toBe(5_000)
        ->and($flat->priceFor(null)?->poisha())->toBe(5_000)
        ->and($perSize->priceFor(7)?->poisha())->toBe(20_000)
        ->and($perSize->priceFor(8))->toBeNull()
        ->and($perSize->priceFor(null))->toBeNull();
});
