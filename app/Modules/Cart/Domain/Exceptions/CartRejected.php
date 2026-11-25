<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * A change of the cart was refused. The message is written for the customer.
 */
final class CartRejected extends DomainException
{
    public static function lineNotFound(): self
    {
        return new self('This item is not in your cart any more.');
    }

    public static function cartFull(int $maximum): self
    {
        return new self("Your cart can hold at most {$maximum} different items.");
    }

    public static function tooManyOfOneItem(int $maximum): self
    {
        return new self("You can order at most {$maximum} of the same item.");
    }

    public static function notOrderable(string $reason): self
    {
        return new self($reason);
    }

    public static function changedMeanwhile(): self
    {
        return new self('Your cart was changed at the same moment. Please try again.');
    }
}
