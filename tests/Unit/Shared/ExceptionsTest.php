<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\DomainException;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

it('keeps messages per field in ValidationFailed', function () {
    $failure = ValidationFailed::withErrors([
        'phone' => ['Enter a valid number.'],
        'name' => ['Name is required.', 'Name is too short.'],
    ]);

    expect($failure->errors())->toBe([
        'phone' => ['Enter a valid number.'],
        'name' => ['Name is required.', 'Name is too short.'],
    ]);
});

it('builds a single field error', function () {
    expect(ValidationFailed::forField('email', 'Bad e-mail.')->errors())->toBe(['email' => ['Bad e-mail.']]);
});

it('describes a missing entity', function () {
    expect(EntityNotFound::withId('Product', 5)->getMessage())->toBe('Product with id 5 was not found.');
});

it('makes every business error catchable as DomainException', function (Throwable $error) {
    expect($error)->toBeInstanceOf(DomainException::class);
})->with([
    'invalid value' => fn () => InvalidValue::because('x'),
    'validation failed' => fn () => ValidationFailed::forField('a', 'b'),
    'entity not found' => fn () => EntityNotFound::withId('Order', 1),
]);
