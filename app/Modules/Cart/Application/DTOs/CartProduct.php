<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

/**
 * What the cart shows about one product.
 */
final readonly class CartProduct
{
    /**
     * @param  bool  $isStandard  a normal item: no combo and no build-your-own pizza
     * @param  ?string  $slug  for the link to its page; null when customers cannot open the page
     * @param  ?string  $imagePath  thumbnail, relative to the public disk
     */
    public function __construct(
        public int $id,
        public string $name,
        public bool $isStandard,
        public ?string $slug,
        public ?string $imagePath,
    ) {}
}
