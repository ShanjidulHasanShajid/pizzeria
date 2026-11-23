<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Exceptions;

use App\Modules\Customization\Domain\Enums\PricingFailureReason;
use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * The pricing engine refused a line. $reason says why (for code), the message says it for people.
 * $optionGroupId is the group that broke a rule, or null when the product or size is the reason.
 */
final class PricingFailed extends DomainException
{
    private function __construct(
        public readonly PricingFailureReason $reason,
        string $message,
        public readonly ?int $optionGroupId = null,
    ) {
        parent::__construct($message);
    }

    public static function wrongProduct(): self
    {
        return new self(PricingFailureReason::WrongProduct, 'These choices belong to another product.');
    }

    public static function productUnpublished(string $product): self
    {
        return new self(PricingFailureReason::ProductUnpublished, "\"{$product}\" is not on the menu.");
    }

    public static function productSoldOut(string $product): self
    {
        return new self(PricingFailureReason::ProductSoldOut, "\"{$product}\" is sold out right now.");
    }

    public static function unknownVariant(string $product): self
    {
        return new self(PricingFailureReason::UnknownVariant, "Choose a size of \"{$product}\".");
    }

    public static function variantSoldOut(string $product, string $variant): self
    {
        return new self(PricingFailureReason::VariantSoldOut, "\"{$product}\" ({$variant}) is sold out right now.");
    }

    public static function groupNotOffered(): self
    {
        return new self(PricingFailureReason::GroupNotOffered, 'Some of the chosen extras are not offered for this item.');
    }

    public static function missingRequiredGroup(string $group, ?int $optionGroupId = null): self
    {
        return new self(PricingFailureReason::MissingRequiredGroup, "Please choose: {$group}.", $optionGroupId);
    }

    public static function tooFewChoices(string $group, int $minimum, ?int $optionGroupId = null): self
    {
        return new self(PricingFailureReason::TooFewChoices, "{$group}: choose at least {$minimum}.", $optionGroupId);
    }

    public static function tooManyChoices(string $group, int $maximum, ?int $optionGroupId = null): self
    {
        return new self(PricingFailureReason::TooManyChoices, "{$group}: choose at most {$maximum}.", $optionGroupId);
    }

    public static function optionNotInGroup(string $group, ?int $optionGroupId = null): self
    {
        return new self(PricingFailureReason::OptionNotInGroup, "{$group}: one of the choices is not offered here.", $optionGroupId);
    }

    public static function optionSoldOut(string $option, ?int $optionGroupId = null): self
    {
        return new self(PricingFailureReason::OptionSoldOut, "\"{$option}\" is sold out right now.", $optionGroupId);
    }

    public static function optionNotOfferedForSize(string $option, string $variant, ?int $optionGroupId = null): self
    {
        return new self(PricingFailureReason::OptionNotOfferedForSize, "\"{$option}\" is not offered for {$variant}.", $optionGroupId);
    }
}
