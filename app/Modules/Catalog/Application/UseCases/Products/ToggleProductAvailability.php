<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * The quick "Sold out today" switch on the product list: mark sold out or available again.
 * The product stays on the site either way. Returns the new state (true = available).
 */
final class ToggleProductAvailability
{
    public function __construct(private readonly ProductRepository $products) {}

    public function handle(CatalogItemInput $input): bool
    {
        $product = $this->products->find($input->id)
            ?? throw EntityNotFound::withId('Product', $input->id);

        if ($product->isAvailable()) {
            $product->markSoldOut();
        } else {
            $product->markAvailable();
        }

        $this->products->save($product);

        return $product->isAvailable();
    }
}
