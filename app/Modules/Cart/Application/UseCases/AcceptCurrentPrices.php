<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\Services\CartEditor;
use App\Modules\Cart\Application\Services\CartPricer;
use App\Modules\Cart\Domain\Entities\Cart;

/**
 * The customer read "the price has changed" and goes on: the prices of this moment become the
 * last seen ones. This only ends the notice; what is charged always comes from the pricing engine.
 */
final class AcceptCurrentPrices
{
    public function __construct(
        private readonly CartEditor $editor,
        private readonly CartPricer $pricer,
    ) {}

    public function handle(): void
    {
        $this->editor->edit(function (Cart $cart): void {
            foreach ($this->pricer->price($cart)->lines as $line) {
                if ($line->unitPrice !== null) {
                    $cart->rememberUnitPrice($line->lineId, $line->unitPrice);
                }
            }
        });
    }
}
