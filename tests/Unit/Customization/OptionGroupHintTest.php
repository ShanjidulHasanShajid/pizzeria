<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\ProductOptionGroupView;
use App\Modules\Customization\Presentation\Support\OptionGroupHint;

it('writes the rules of a group in words', function (bool $isRequired, int $minimum, int $maximum, int $freeCount, string $hint): void {
    $group = new ProductOptionGroupView(1, 'Toppings', null, $isRequired, $minimum, $maximum, $freeCount, []);

    expect(OptionGroupHint::for($group))->toBe($hint);
})->with([
    'exactly one' => [true, 1, 1, 0, 'Required, choose 1'],
    'required, several' => [true, 1, 3, 0, 'Required, choose 1 to 3'],
    'optional, one' => [false, 0, 1, 0, 'Choose up to 1'],
    'optional, several' => [false, 0, 5, 0, 'Choose up to 5'],
    'from two' => [false, 2, 4, 0, 'Choose 2 to 4'],
    'one free' => [false, 0, 5, 1, 'Choose up to 5, 1 is free'],
    'some free' => [true, 2, 2, 2, 'Required, choose 2, 2 are free'],
]);
