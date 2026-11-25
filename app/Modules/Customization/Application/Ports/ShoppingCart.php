<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Ports;

use App\Modules\Customization\Application\DTOs\AddToCartInput;
use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * Where a configured item goes. An adapter in Infrastructure hands it to the Cart module.
 */
interface ShoppingCart
{
    /**
     * @throws DomainException when the item is refused; the message is for the customer
     */
    public function add(AddToCartInput $input): void;
}
