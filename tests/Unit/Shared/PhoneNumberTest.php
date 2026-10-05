<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

it('normalizes every common way of writing a number', function (string $input) {
    expect(PhoneNumber::fromString($input)->value())->toBe('01712345678');
})->with([
    '01712345678',
    '+8801712345678',
    '8801712345678',
    '017 1234 5678',
    '017-1234-5678',
    '(017) 12345678',
    ' 01712345678 ',
    '০১৭১২৩৪৫৬৭৮',
]);

it('rejects numbers that are not Bangladesh mobiles', function (string $input) {
    expect(PhoneNumber::tryFrom($input))->toBeNull();
})->with([
    '',
    'abc',
    '1712345678',
    '0171234567',
    '017123456789',
    '01212345678',
    '+8801712345678 9',
    '029876543',
]);

it('throws InvalidValue from fromString', function () {
    expect(fn () => PhoneNumber::fromString('12345'))->toThrow(InvalidValue::class);
});

it('gives the international form', function () {
    expect(PhoneNumber::fromString('01712345678')->international())->toBe('+8801712345678');
});

it('compares by value', function () {
    expect(PhoneNumber::fromString('01712345678')->equals(PhoneNumber::fromString('+8801712345678')))->toBeTrue();
});
