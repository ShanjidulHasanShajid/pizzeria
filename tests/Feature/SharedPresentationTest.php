<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Presentation\Rules\BangladeshPhone;
use App\Modules\Shared\Presentation\Support\ValidationFailedTranslator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

it('prints poisha with the @money directive', function () {
    $html = Blade::render('@money($amount)', ['amount' => 125000]);

    expect(trim($html))->toBe('৳1,250');
});

it('prints a Money object with the @money directive', function () {
    $html = Blade::render('@money($amount)', ['amount' => Money::fromPoisha(125050)]);

    expect(trim($html))->toBe('৳1,250.50');
});

it('accepts a valid phone with the BangladeshPhone rule', function (string $phone) {
    $validator = Validator::make(['phone' => $phone], ['phone' => [new BangladeshPhone]]);

    expect($validator->passes())->toBeTrue();
})->with(['01712345678', '+8801712345678', '017-1234-5678']);

it('rejects an invalid phone with the BangladeshPhone rule', function (mixed $phone) {
    $validator = Validator::make(['phone' => $phone], ['phone' => [new BangladeshPhone]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('phone'))->toContain('valid Bangladesh mobile number');
})->with(['12345', 'abc', '01212345678', 12345678901]);

it('turns ValidationFailed into a Laravel validation error', function () {
    $failure = ValidationFailed::withErrors(['phone' => ['Enter a valid number.']]);

    try {
        throw (new ValidationFailedTranslator)->toValidationException($failure);
    } catch (ValidationException $error) {
        expect($error->errors())->toBe(['phone' => ['Enter a valid number.']]);
    }
});
