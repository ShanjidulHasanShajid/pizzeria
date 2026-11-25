<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\DTOs\AddStandardItemInput;
use App\Modules\Cart\Application\DTOs\LineToPrice;
use App\Modules\Cart\Application\Ports\CartProducts;
use App\Modules\Cart\Application\Ports\LinePricing;
use App\Modules\Cart\Application\Services\CartEditor;
use App\Modules\Cart\Application\Services\CartPricer;
use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Domain\ValueObjects\ChosenOptions;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * Puts a normal product into the cart. The pricing engine checks every choice and gives the
 * price; a line that cannot be ordered right now is refused.
 */
final class AddStandardItem
{
    public function __construct(
        private readonly CartProducts $products,
        private readonly LinePricing $pricing,
        private readonly CartEditor $editor,
    ) {}

    public function handle(AddStandardItemInput $input): void
    {
        $options = ChosenOptions::of($input->choices);
        $note = KitchenNote::of($input->note);
        $quantity = Quantity::of($input->quantity);

        $product = $this->products->byIds([$input->productId])[$input->productId]
            ?? throw CartRejected::notOrderable(CartPricer::GONE);

        if (! $product->isStandard) {
            throw CartRejected::notOrderable("\"{$product->name}\" cannot be ordered like this.");
        }

        $quote = $this->pricing->quote([
            new LineToPrice($input->productId, $input->variantId, $options->toArray(), $quantity->value()),
        ])[0];

        if ($quote->unitPrice === null) {
            throw CartRejected::notOrderable($quote->problem ?? CartPricer::GONE);
        }

        $line = CartLine::standard($input->productId, $input->variantId, $options, $note, $quantity, $quote->unitPrice);

        $this->editor->editOrCreate(fn (Cart $cart) => $cart->addLine($line));
    }
}
