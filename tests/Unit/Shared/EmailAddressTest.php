<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;

it('trims and lower-cases the address', function () {
    expect(EmailAddress::fromString('  Ali@Example.COM ')->value())->toBe('ali@example.com');
});

it('rejects addresses that are not valid', function (string $value) {
    expect(fn () => EmailAddress::fromString($value))->toThrow(InvalidValue::class);
})->with(['', 'ali', 'ali@', '@example.com', 'ali example@mail.com']);

it('rejects an address longer than 254 characters', function () {
    expect(fn () => EmailAddress::fromString(str_repeat('a', 250).'@example.com'))->toThrow(InvalidValue::class);
});

it('compares by value', function () {
    expect(EmailAddress::fromString('A@b.com')->equals(EmailAddress::fromString('a@B.com')))->toBeTrue();
});
