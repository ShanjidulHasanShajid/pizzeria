<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Services;

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Exceptions\PricingFailed;
use App\Modules\Customization\Domain\ValueObjects\LineSelection;
use App\Modules\Customization\Domain\ValueObjects\PricedLine;
use App\Modules\Customization\Domain\ValueObjects\PricedOption;
use App\Modules\Customization\Domain\ValueObjects\PricingProduct;
use App\Modules\Customization\Domain\ValueObjects\PricingVariant;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * The one place where a line is priced (business rules section 4):
 * the chosen variant's price + every chosen option at the variant's size,
 * checked against the rules of each attached group, with the cheapest picks free.
 * Pure PHP: everything it needs is passed in, so it is fast and easy to test.
 */
final class PricingEngine
{
    /**
     * @param  list<ProductOptionGroup>  $attachments  the option groups attached to this product
     * @param  list<OptionGroup>  $groups  those option groups, with their options
     */
    public function price(PricingProduct $product, LineSelection $selection, array $attachments, array $groups): PricedLine
    {
        if ($selection->productId() !== $product->id()) {
            throw PricingFailed::wrongProduct();
        }

        if (! $product->isPublished()) {
            throw PricingFailed::productUnpublished($product->name());
        }

        if (! $product->isAvailable()) {
            throw PricingFailed::productSoldOut($product->name());
        }

        $variant = $product->variant($selection->variantId())
            ?? throw PricingFailed::unknownVariant($product->name());

        if (! $variant->isAvailable()) {
            throw PricingFailed::variantSoldOut($product->name(), $variant->label());
        }

        $offered = $this->offeredGroups($product, $attachments, $groups);

        foreach ($selection->optionGroupIds() as $optionGroupId) {
            if (! isset($offered[$optionGroupId])) {
                throw PricingFailed::groupNotOffered();
            }
        }

        $pricedOptions = [];

        foreach ($offered as $optionGroupId => [$attachment, $group]) {
            $picks = $this->priceGroup($optionGroupId, $group, $attachment->rules(), $selection->optionIdsFor($optionGroupId), $variant);
            array_push($pricedOptions, ...$picks);
        }

        return PricedLine::fromParts(
            $product->id(),
            $product->name(),
            $variant->id(),
            $variant->label(),
            $variant->price(),
            $pricedOptions,
            $selection->quantity(),
        );
    }

    /**
     * The groups this product really offers: attached to it and active, in the product's order.
     * An inactive group counts as not attached: no requirement, and picks from it are refused.
     *
     * @param  list<ProductOptionGroup>  $attachments
     * @param  list<OptionGroup>  $groups
     * @return array<int, array{0: ProductOptionGroup, 1: OptionGroup}> option group id => [attachment, group]
     */
    private function offeredGroups(PricingProduct $product, array $attachments, array $groups): array
    {
        $activeGroups = [];

        foreach ($groups as $group) {
            if ($group->id() !== null && $group->isActive()) {
                $activeGroups[$group->id()] = $group;
            }
        }

        $ownAttachments = array_filter(
            $attachments,
            fn (ProductOptionGroup $attachment): bool => $attachment->productId() === $product->id()
                && isset($activeGroups[$attachment->optionGroupId()]),
        );
        usort($ownAttachments, fn (ProductOptionGroup $a, ProductOptionGroup $b): int => $a->sort()->value() <=> $b->sort()->value());

        $offered = [];

        foreach ($ownAttachments as $attachment) {
            $offered[$attachment->optionGroupId()] = [$attachment, $activeGroups[$attachment->optionGroupId()]];
        }

        return $offered;
    }

    /**
     * Checks the picks of one group against its rules and prices them for the variant's size.
     *
     * @param  list<int>  $optionIds
     * @return list<PricedOption>
     */
    private function priceGroup(int $optionGroupId, OptionGroup $group, SelectionRules $rules, array $optionIds, PricingVariant $variant): array
    {
        $title = $group->displayTitle();
        $count = count($optionIds);

        if ($count === 0) {
            if ($rules->isRequired()) {
                throw PricingFailed::missingRequiredGroup($title);
            }

            return [];
        }

        if ($count < $rules->minimum()) {
            throw PricingFailed::tooFewChoices($title, $rules->minimum());
        }

        if ($count > $rules->maximum()) {
            throw PricingFailed::tooManyChoices($title, $rules->maximum());
        }

        $options = []; // option id => Option
        $prices = [];  // option id => price at this size

        foreach ($optionIds as $optionId) {
            $option = $group->option($optionId) ?? throw PricingFailed::optionNotInGroup($title);

            if (! $option->isAvailable()) {
                throw PricingFailed::optionSoldOut($option->name());
            }

            $options[$optionId] = $option;
            $prices[$optionId] = $option->priceFor($variant->sizeTierId())
                ?? throw PricingFailed::optionNotOfferedForSize($option->name(), $variant->label());
        }

        $freeIds = $this->cheapest($prices, $rules->freeCount());

        // Show the picks in the group's own order, not in the order they were clicked.
        uasort($options, fn (Option $a, Option $b): int => $a->sort()->value() <=> $b->sort()->value());

        $priced = [];

        foreach ($options as $optionId => $option) {
            $priced[] = new PricedOption(
                $optionGroupId,
                $title,
                $optionId,
                $option->name(),
                $prices[$optionId],
                in_array($optionId, $freeIds, true) ? Money::zero() : $prices[$optionId],
            );
        }

        return $priced;
    }

    /**
     * The ids of the $count cheapest picks ("the cheapest N picks are free").
     * On equal prices the lower id wins, so the result is always the same.
     *
     * @param  array<int, Money>  $prices  option id => price
     * @return list<int>
     */
    private function cheapest(array $prices, int $count): array
    {
        if ($count === 0) {
            return [];
        }

        $ids = array_keys($prices);
        usort($ids, fn (int $a, int $b): int => [$prices[$a]->poisha(), $a] <=> [$prices[$b]->poisha(), $b]);

        return array_slice($ids, 0, $count);
    }
}
