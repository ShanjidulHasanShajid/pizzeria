<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Enums;

/**
 * Why a line could not be priced. The cart (Phase 11) uses the reason to decide what to do,
 * for example drop a sold-out option with a notice; the message is for the customer.
 */
enum PricingFailureReason: string
{
    case WrongProduct = 'wrong_product';
    case ProductUnpublished = 'product_unpublished';
    case ProductSoldOut = 'product_sold_out';
    case UnknownVariant = 'unknown_variant';
    case VariantSoldOut = 'variant_sold_out';
    case GroupNotOffered = 'group_not_offered';
    case MissingRequiredGroup = 'missing_required_group';
    case TooFewChoices = 'too_few_choices';
    case TooManyChoices = 'too_many_choices';
    case OptionNotInGroup = 'option_not_in_group';
    case OptionSoldOut = 'option_sold_out';
    case OptionNotOfferedForSize = 'option_not_offered_for_size';
}
