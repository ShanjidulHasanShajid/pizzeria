<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Services;

use App\Modules\Cart\Application\DTOs\CartProduct;
use App\Modules\Cart\Application\DTOs\LineQuote;
use App\Modules\Cart\Application\DTOs\LineToPrice;
use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Cart\Application\DTOs\PricedCartLine;
use App\Modules\Cart\Application\Ports\CartProducts;
use App\Modules\Cart\Application\Ports\LinePricing;
use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\Enums\CartLineStatus;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * Re-prices every line with the prices of this moment and says what happened to it.
 * It changes nothing: the cart keeps the last seen price until the customer does something.
 */
final class CartPricer
{
    public const GONE = 'This item is no longer on the menu.';

    public function __construct(
        private readonly LinePricing $pricing,
        private readonly CartProducts $products,
    ) {}

    public function price(Cart $cart): PricedCart
    {
        $lines = $cart->lines();

        if ($lines === []) {
            return PricedCart::empty();
        }

        $quotes = $this->pricing->quote(array_map(fn (CartLine $line): LineToPrice => new LineToPrice(
            $line->productId(),
            $line->variantId(),
            $line->options()->toArray(),
            $line->quantity()->value(),
        ), $lines));
        $products = $this->products->byIds(array_values(array_unique(
            array_map(fn (CartLine $line): int => $line->productId(), $lines),
        )));

        $priced = [];
        $subtotal = Money::zero();

        foreach ($lines as $position => $line) {
            $quote = $quotes[$position] ?? LineQuote::refused(self::GONE);
            $priced[] = $this->pricedLine($line, $quote, $products[$line->productId()] ?? null);
            $subtotal = $quote->lineTotal === null ? $subtotal : $subtotal->add($quote->lineTotal);
        }

        return new PricedCart($priced, $subtotal);
    }

    private function pricedLine(CartLine $line, LineQuote $quote, ?CartProduct $product): PricedCartLine
    {
        return new PricedCartLine(
            lineId: (int) $line->id(),
            productId: $line->productId(),
            productName: $product === null ? 'Unknown item' : $product->name,
            productSlug: $product?->slug,
            imagePath: $product?->imagePath,
            variantLabel: $quote->variantLabel,
            options: $quote->options,
            note: $line->note()->text(),
            quantity: $line->quantity()->value(),
            unitPrice: $quote->unitPrice,
            lineTotal: $quote->lineTotal,
            lastUnitPrice: $line->lastUnitPrice(),
            status: $this->status($line, $quote),
            problem: $quote->problem,
        );
    }

    private function status(CartLine $line, LineQuote $quote): CartLineStatus
    {
        if ($quote->unitPrice === null) {
            return CartLineStatus::Unavailable;
        }

        return $line->hasPriceChangedTo($quote->unitPrice) ? CartLineStatus::PriceChanged : CartLineStatus::Ok;
    }
}
