<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\ProductImage;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('creates an image that is not the main one yet', function (): void {
    $image = ProductImage::create(ImagePath::of('products/a.webp', 'products/thumbs/a.webp'), SortOrder::first());

    expect($image->id())->toBeNull()
        ->and($image->isMain())->toBeFalse()
        ->and($image->path()->thumbnail())->toBe('products/thumbs/a.webp');
});

it('can be marked as main and moved', function (): void {
    $image = ProductImage::reconstitute(4, ImagePath::of('products/a.webp'), false, SortOrder::first());

    $image->markAsMain();
    $image->moveTo(SortOrder::fromInt(30));

    expect($image->isMain())->toBeTrue()
        ->and($image->sort()->value())->toBe(30);

    $image->unmarkAsMain();

    expect($image->isMain())->toBeFalse();
});
