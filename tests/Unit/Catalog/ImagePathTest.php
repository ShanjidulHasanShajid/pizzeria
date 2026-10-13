<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;

it('keeps the original and the thumbnail', function (): void {
    $image = ImagePath::of('products/margherita.webp', 'products/thumbs/margherita.webp');

    expect($image->original())->toBe('products/margherita.webp')
        ->and($image->thumbnail())->toBe('products/thumbs/margherita.webp')
        ->and($image->hasThumbnail())->toBeTrue();
});

it('falls back to the original when there is no thumbnail', function (): void {
    $image = ImagePath::of('categories/pizza.jpg');

    expect($image->thumbnail())->toBe('categories/pizza.jpg')
        ->and($image->hasThumbnail())->toBeFalse();
});

it('rejects unsafe or non-image paths', function (string $path): void {
    expect(fn () => ImagePath::of($path))->toThrow(InvalidValue::class);
})->with([
    'empty' => '',
    'parent folder' => '../secret.jpg',
    'parent folder inside' => 'products/../../.env.jpg',
    'absolute' => '/var/www/a.jpg',
    'windows slashes' => 'products\\a.jpg',
    'hidden file' => 'products/.a.jpg',
    'not an image' => 'products/a.php',
    'double slash' => 'products//a.jpg',
]);

it('rejects a path that is too long', function (): void {
    expect(fn () => ImagePath::of(str_repeat('a', 252).'.jpg'))->toThrow(InvalidValue::class);
});

it('checks the thumbnail too', function (): void {
    expect(fn () => ImagePath::of('products/a.webp', '../b.webp'))->toThrow(InvalidValue::class);
});

it('compares by value', function (): void {
    expect(ImagePath::of('a.webp', 'b.webp')->equals(ImagePath::of('a.webp', 'b.webp')))->toBeTrue()
        ->and(ImagePath::of('a.webp')->equals(ImagePath::of('a.webp', 'b.webp')))->toBeFalse();
});
