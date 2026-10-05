<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Presentation\Support\MoneyFormatter;

it('formats poisha as taka with a thousands separator', function (int $poisha, string $expected) {
    expect((new MoneyFormatter)->format($poisha))->toBe($expected);
})->with([
    'zero' => [0, '৳0'],
    'whole taka' => [125000, '৳1,250'],
    'with poisha' => [125050, '৳1,250.50'],
    'only poisha' => [5, '৳0.05'],
    'millions' => [123456700, '৳1,234,567'],
    'negative' => [-250, '-৳2.50'],
]);

it('formats a Money object', function () {
    expect((new MoneyFormatter)->format(Money::fromTaka('450')))->toBe('৳450');
});
