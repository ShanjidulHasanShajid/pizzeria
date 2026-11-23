<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * One option group on one product, for the Options section of the product form.
 */
final readonly class AttachedGroupRow
{
    public function __construct(
        public int $attachmentId,
        public int $optionGroupId,
        public string $groupName,
        public bool $isGroupActive,
        public int $optionCount,
        public bool $isRequired,
        public int $minimum,
        public int $maximum,
        public int $freeCount,
    ) {}
}
