<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * What another module needs to show a product it only knows by id (a cart line).
 */
final readonly class ProductSummary
{
    /**
     * @param  bool  $isStandard  a normal item: no combo and no build-your-own pizza
     * @param  bool  $isVisible  customers can open its page
     * @param  ?string  $imagePath  thumbnail of the main image, relative to the public disk
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public bool $isStandard,
        public bool $isVisible,
        public ?string $imagePath,
    ) {}
}
