<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Slug;

it('turns a title into a slug', function (string $title, string $expected) {
    $slug = (new SlugGenerator)->generate($title, fn (Slug $slug): bool => false);

    expect($slug->value())->toBe($expected);
})->with([
    ['Margherita Pizza', 'margherita-pizza'],
    ['Burgers & Sandwiches', 'burgers-and-sandwiches'],
    ['  Hot   Wings!!  ', 'hot-wings'],
    ['7UP 1.5L', '7up-1-5l'],
    ['পিৎজা', 'item'],
]);

it('adds a number when the slug is taken', function () {
    $taken = ['margherita', 'margherita-2'];

    $slug = (new SlugGenerator)->generate(
        'Margherita',
        fn (Slug $candidate): bool => in_array($candidate->value(), $taken, true),
    );

    expect($slug->value())->toBe('margherita-3');
});

it('keeps the slug within 120 characters even when numbered', function () {
    $slug = (new SlugGenerator)->generate(
        str_repeat('a', 300),
        fn (Slug $candidate): bool => strlen($candidate->value()) === 114,
    );

    expect(strlen($slug->value()))->toBeLessThanOrEqual(120);
});

it('gives up when every slug is taken', function () {
    expect(fn () => (new SlugGenerator)->generate('Pizza', fn (): bool => true))
        ->toThrow(InvalidValue::class);
});
