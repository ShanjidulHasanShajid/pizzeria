<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\DTOs\ChangeLineQuantityInput;
use App\Modules\Cart\Application\Services\CartEditor;
use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * Sets how many of a line the customer wants: 1 to 20.
 */
final class ChangeLineQuantity
{
    public function __construct(private readonly CartEditor $editor) {}

    public function handle(ChangeLineQuantityInput $input): void
    {
        $quantity = Quantity::of($input->quantity);

        if (! $this->editor->edit(fn (Cart $cart) => $cart->changeQuantity($input->lineId, $quantity))) {
            throw CartRejected::lineNotFound();
        }
    }
}
