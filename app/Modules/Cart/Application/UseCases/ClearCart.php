<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\Services\CartEditor;
use App\Modules\Cart\Domain\Entities\Cart;

/**
 * Takes everything out. Without a cart there is nothing to do.
 */
final class ClearCart
{
    public function __construct(private readonly CartEditor $editor) {}

    public function handle(): void
    {
        $this->editor->edit(fn (Cart $cart) => $cart->clear());
    }
}
