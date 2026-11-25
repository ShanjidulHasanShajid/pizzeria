<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases;

use App\Modules\Customization\Application\DTOs\PricedLineView;
use App\Modules\Customization\Application\DTOs\QuotedLine;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\Ports\PricingProductSource;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\Services\PricingEngine;
use App\Modules\Customization\Domain\ValueObjects\LineSelection;
use App\Modules\Customization\Domain\ValueObjects\PricingProduct;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * QuoteLine for many lines at once (the cart). Everything is loaded once for all lines, and a
 * refused line is an answer, not an exception: the other lines still get their price.
 */
final class QuoteLines
{
    public function __construct(
        private readonly PricingProductSource $products,
        private readonly ProductOptionGroupRepository $attachments,
        private readonly OptionGroupRepository $groups,
        private readonly PricingEngine $engine,
    ) {}

    /**
     * @param  array<int, QuoteLineInput>  $inputs
     * @return array<int, QuotedLine> one answer per input, with the same keys
     */
    public function handle(array $inputs): array
    {
        if ($inputs === []) {
            return [];
        }

        $productIds = array_values(array_unique(array_map(fn (QuoteLineInput $input): int => $input->productId, $inputs)));
        $products = $this->products->findMany($productIds);
        $attachments = $this->attachments->forProducts($productIds);
        $groups = $this->groups->findByIds(array_values(array_unique(array_map(
            fn (ProductOptionGroup $attachment): int => $attachment->optionGroupId(),
            $attachments,
        ))));

        return array_map(
            fn (QuoteLineInput $input): QuotedLine => $this->quote($input, $products[$input->productId] ?? null, $attachments, $groups),
            $inputs,
        );
    }

    /**
     * The engine uses only the attachments of this product, so it may get those of all products.
     *
     * @param  list<ProductOptionGroup>  $attachments
     * @param  list<OptionGroup>  $groups
     */
    private function quote(QuoteLineInput $input, ?PricingProduct $product, array $attachments, array $groups): QuotedLine
    {
        if ($product === null) {
            return QuotedLine::refused('This item is no longer on the menu.');
        }

        try {
            $selection = LineSelection::of($input->productId, $input->variantId, $input->choices, Quantity::of($input->quantity));

            return QuotedLine::priced(PricedLineView::fromPricedLine($this->engine->price($product, $selection, $attachments, $groups)));
        } catch (DomainException $refused) {
            return QuotedLine::refused($refused->getMessage());
        }
    }
}
