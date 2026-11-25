<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Application\Services\CartPricer;
use App\Modules\Cart\Domain\Repositories\CartRepository;

/**
 * The cart of this visitor with the prices of this moment. It changes nothing.
 */
final class GetPricedCart
{
    public function __construct(
        private readonly CartContext $context,
        private readonly CartRepository $carts,
        private readonly CartPricer $pricer,
    ) {}

    public function handle(): PricedCart
    {
        $owner = $this->context->current();
        $cart = $owner === null ? null : $this->carts->find($owner);

        return $cart === null ? PricedCart::empty() : $this->pricer->price($cart);
    }
}
