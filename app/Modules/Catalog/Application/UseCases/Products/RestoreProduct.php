<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;

/**
 * Brings a product back from the Trash with its variants, images and tags.
 */
final class RestoreProduct
{
    public function __construct(private readonly ProductRepository $products) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->products->restore($input->id);
    }
}
