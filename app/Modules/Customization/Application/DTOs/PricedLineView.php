<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

use App\Modules\Customization\Domain\ValueObjects\PricedLine;
use App\Modules\Customization\Domain\ValueObjects\PricedOption;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * A priced line for the outside world. Other modules (the cart, later) may use this class,
 * but not the domain's PricedLine.
 */
final readonly class PricedLineView
{
    /**
     * @param  list<PricedOptionView>  $options
     * @param  list<string>  $breakdown  readable lines, for example "Crust: Stuffed +৳150.00"
     */
    public function __construct(
        public int $productId,
        public string $productName,
        public int $variantId,
        public string $variantLabel,
        public Money $basePrice,
        public array $options,
        public int $quantity,
        public Money $unitPrice,
        public Money $lineTotal,
        public array $breakdown,
    ) {}

    public static function fromPricedLine(PricedLine $line): self
    {
        return new self(
            $line->productId(),
            $line->productName(),
            $line->variantId(),
            $line->variantLabel(),
            $line->basePrice(),
            array_map(fn (PricedOption $option): PricedOptionView => PricedOptionView::fromPricedOption($option), $line->options()),
            $line->quantity()->value(),
            $line->unitPrice(),
            $line->lineTotal(),
            $line->breakdown(),
        );
    }
}
