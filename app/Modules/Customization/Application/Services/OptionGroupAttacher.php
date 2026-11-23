<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Services;

use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Attaches a group at the end of a product's list. Shared by "attach" and "bulk attach".
 */
final class OptionGroupAttacher
{
    public function __construct(private readonly ProductOptionGroupRepository $attachments) {}

    /**
     * Returns the id of the new attachment, or null when the product already has the group.
     */
    public function attach(int $productId, int $optionGroupId, SelectionRules $rules): ?int
    {
        $sort = SortOrder::first();

        foreach ($this->attachments->forProduct($productId) as $attachment) {
            if ($attachment->optionGroupId() === $optionGroupId) {
                return null;
            }

            $sort = $attachment->sort()->next();
        }

        return $this->attachments->save(ProductOptionGroup::attach($productId, $optionGroupId, $rules, $sort));
    }
}
