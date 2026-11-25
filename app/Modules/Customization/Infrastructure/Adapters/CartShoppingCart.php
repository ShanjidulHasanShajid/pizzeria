<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Adapters;

use App\Modules\Cart\Application\DTOs\AddStandardItemInput;
use App\Modules\Cart\Application\UseCases\AddStandardItem;
use App\Modules\Customization\Application\DTOs\AddToCartInput;
use App\Modules\Customization\Application\Ports\ShoppingCart;

/**
 * Puts the item into the cart, through the Application layer of the Cart module.
 */
final class CartShoppingCart implements ShoppingCart
{
    public function __construct(private readonly AddStandardItem $addItem) {}

    public function add(AddToCartInput $input): void
    {
        $this->addItem->handle(new AddStandardItemInput(
            $input->productId,
            $input->variantId,
            $input->choices,
            $input->note,
            $input->quantity,
        ));
    }
}
