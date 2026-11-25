<?php

declare(strict_types=1);

use App\Modules\Ordering\Domain\ValueObjects\OrderNumber;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;

it('accepts what a customer may type', function (string $input): void {
    expect(OrderNumber::tryFrom($input)?->value())->toBe('PZ-7K4M2Q');
})->with([
    'as printed' => 'PZ-7K4M2Q',
    'small letters' => 'pz-7k4m2q',
    'no prefix' => '7K4M2Q',
    'no dash' => 'PZ7K4M2Q',
    'spaces' => '  PZ - 7K4 M2Q ',
    'a long dash' => 'PZ–7K4M2Q',
]);

it('refuses what cannot be an order number', function (string $input): void {
    expect(OrderNumber::tryFrom($input))->toBeNull()
        ->and(fn () => OrderNumber::fromString($input))->toThrow(InvalidValue::class);
})->with([
    'empty' => '',
    'too short' => 'PZ-7K4M2',
    'too long' => 'PZ-7K4M2QQ',
    'a zero' => 'PZ-0K4M2Q',
    'a vowel' => 'PZ-AK4M2Q',
    'another prefix' => 'XX-7K4M2Q',
    'Bengali digits' => 'PZ-৭K4M2Q',
    'broken text' => "PZ-7K4M2\xff",
]);

it('reads a code that itself starts with PZ', function (): void {
    expect(OrderNumber::fromString('PZ7K4M')->value())->toBe('PZ-PZ7K4M')
        ->and(OrderNumber::fromString('PZ-PZ7K4M')->value())->toBe('PZ-PZ7K4M');
});

it('has no characters that are mixed up and no vowels', function (): void {
    expect(strpbrk(OrderNumber::ALPHABET, '01ILOAEU'))->toBeFalse()
        ->and(strlen(count_chars(OrderNumber::ALPHABET, 3)))->toBe(strlen(OrderNumber::ALPHABET))
        ->and(strlen(OrderNumber::ALPHABET))->toBe(28);
});

it('compares by value', function (): void {
    expect(OrderNumber::fromString('PZ-7K4M2Q')->equals(OrderNumber::fromString('7k4m2q')))->toBeTrue()
        ->and(OrderNumber::fromString('PZ-7K4M2Q')->equals(OrderNumber::fromString('PZ-7K4M2R')))->toBeFalse();
});
