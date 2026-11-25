<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Adapters;

use App\Modules\Cart\Application\DTOs\LineQuote;
use App\Modules\Cart\Application\DTOs\LineToPrice;
use App\Modules\Cart\Application\DTOs\PricedCartOption;
use App\Modules\Cart\Application\Ports\LinePricing;
use App\Modules\Customization\Application\DTOs\PricedOptionView;
use App\Modules\Customization\Application\DTOs\QuotedLine;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\UseCases\QuoteLines;

/**
 * Asks the pricing engine, through the Application layer of the Customization module.
 */
final class CustomizationLinePricing implements LinePricing
{
    public function __construct(private readonly QuoteLines $quoteLines) {}

    public function quote(array $lines): array
    {
        $quotes = $this->quoteLines->handle(array_map(
            fn (LineToPrice $line): QuoteLineInput => new QuoteLineInput($line->productId, $line->variantId, $line->choices, $line->quantity),
            $lines,
        ));

        return array_map($this->toQuote(...), $quotes);
    }

    private function toQuote(QuotedLine $quoted): LineQuote
    {
        $line = $quoted->line;

        if ($line === null) {
            return LineQuote::refused((string) $quoted->problem);
        }

        return LineQuote::priced(
            $line->variantLabel,
            array_map(fn (PricedOptionView $option): PricedCartOption => new PricedCartOption(
                $option->groupTitle,
                $option->optionName,
                $option->charged,
                $option->isFreePick,
            ), $line->options),
            $line->unitPrice,
            $line->lineTotal,
        );
    }
}
