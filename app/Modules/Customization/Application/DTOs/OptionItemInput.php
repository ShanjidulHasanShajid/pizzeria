<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * Input for actions on one option of a group: remove, toggle available.
 */
final readonly class OptionItemInput
{
    public function __construct(
        public int $optionGroupId,
        public int $optionId,
    ) {}
}
