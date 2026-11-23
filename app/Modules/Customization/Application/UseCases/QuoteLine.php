<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases;

use App\Modules\Customization\Application\DTOs\PricedLineView;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\Ports\PricingProductSource;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\Services\PricingEngine;
use App\Modules\Customization\Domain\ValueObjects\LineSelection;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * What does this line cost right now? Loads the current prices and rules, then lets the
 * pricing engine decide. It changes nothing. A refused line throws PricingFailed.
 */
final class QuoteLine
{
    public function __construct(
        private readonly PricingProductSource $products,
        private readonly ProductOptionGroupRepository $attachments,
        private readonly OptionGroupRepository $groups,
        private readonly PricingEngine $engine,
    ) {}

    public function handle(QuoteLineInput $input): PricedLineView
    {
        $selection = LineSelection::of(
            $input->productId,
            $input->variantId,
            $input->choices,
            Quantity::of($input->quantity),
        );

        $product = $this->products->find($input->productId)
            ?? throw EntityNotFound::withId('Product', $input->productId);

        $attachments = $this->attachments->forProduct($input->productId);
        $groups = $this->groups->findByIds(array_map(
            fn (ProductOptionGroup $attachment): int => $attachment->optionGroupId(),
            $attachments,
        ));

        return PricedLineView::fromPricedLine($this->engine->price($product, $selection, $attachments, $groups));
    }
}
