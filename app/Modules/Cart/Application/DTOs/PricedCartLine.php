<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

use App\Modules\Cart\Domain\Enums\CartLineStatus;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One line as the cart page shows it, with the price of this moment.
 */
final readonly class PricedCartLine
{
    /**
     * @param  ?string  $productSlug  null when the product page cannot be opened
     * @param  ?string  $variantLabel  null for an unavailable line
     * @param  list<PricedCartOption>  $options  empty for an unavailable line
     * @param  ?Money  $unitPrice  null for an unavailable line
     * @param  ?Money  $lineTotal  null for an unavailable line
     * @param  Money  $lastUnitPrice  what the customer saw before
     * @param  ?string  $problem  why the line is unavailable
     */
    public function __construct(
        public int $lineId,
        public int $productId,
        public string $productName,
        public ?string $productSlug,
        public ?string $imagePath,
        public ?string $variantLabel,
        public array $options,
        public ?string $note,
        public int $quantity,
        public ?Money $unitPrice,
        public ?Money $lineTotal,
        public Money $lastUnitPrice,
        public CartLineStatus $status,
        public ?string $problem,
    ) {}
}
